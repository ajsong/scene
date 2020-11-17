<?php
//Developed by @mario 20201111
defined('ROOT_PATH') or define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
class SQL {
	private static $DB = array(); //实例组
	private static $redis = array(); //redis对象组
	private static $memcached = array(); //memcached对象组
	private $ezr, $smarty, $ip;
	private $tbp, $dbtype, $tbp_placeholder, $key; //实例KEY
	private $non_access_log_tables = array();
	private $table = '';
	private $split_tables = array(); //需要水平分表的表名
	private $dbslave = NULL; //从数据库的数据源
	private $origin_tbp = '';
	private $origin_db = NULL;
	private $origin_dbtype = '';
	private $origin_is_sqlite3 = false;
	private $origin_is_pdo = false;
	private $db = NULL;
	private $is_sqlite3 = false;
	private $is_pdo = false;
	private $left = array();
	private $right = array();
	private $inner = array();
	private $cross = array();
	private $union = array();
	private $unionFields = array();
	private $where = '';
	private $distincter = '';
	private $order = '';
	private $group = '';
	private $having = '';
	private $isezr = false;
	private $firstpage = '';
	private $lastpage = '';
	private $prevpage = '';
	private $nextpage = '';
	private $isJumppage = true;
	private $rewritePage = '';
	private $offset = 0;
	private $pagesize = 0;
	private $cached = 0; //使用serialize缓存查询结果,0不缓存,-1永久缓存,-2使用session缓存,>0缓存时间(单位秒)
	private $returnObj = false; //插入/更新/删除返回操作的记录
	private $returnArray = false; //查询结果转为数组形式
	private $glue = ''; //returnArray为true时把数组合并为字符串的分隔符,空为不合并
	private $skipClient = false; //不自动增加client_id条件
	private $href = ''; //生成用encrypt_param加密的网址字段,例如?app=home&act=index&id=[id],[id]将替换为记录集里面对应的字段值
	private $debug = false; //调式模式,不执行sql
	private $whereParam = array(); //PDO的where参数值
	private $setParam = array(); //PDO的insert,update参数值
	public $log = false; //生命周期内把运行过的sql记录到log
	public $page = ''; //分页HTML代码
	public $pagelink = ''; //分页参数
	public $wherebase64 = ''; //条件筛选参数base64
	public $sql = ''; //最后执行的sql
	public $sqls = array(); //生命周期内执行过的sql
	public $sql_count = 0; //生命周期内数据库查询次数
	private $client_id;
	private $is_operator_platform = false;
	private $non_client_tables = array();
	//构建函数
	public function __construct($db, $key='db_master', $db_config=array()) {
		global $ezr, $smarty;
		if (!class_exists('ezSQLcore')) die('SQL require ezSQLcore component!');
		if (!isset($db)) die('Missing $db instance!');
		$this->db = $db;
		$this->ezr = isset($ezr) ? $ezr : NULL;
		$this->smarty = isset($smarty) ? $smarty : NULL;
		$this->ip = function_exists('ip') ? ip() : '';
		$this->tbp = isset($db_config['prefix']) ? $db_config['prefix'] : '';
		$this->dbtype = isset($db_config['type']) ? strtoupper($db_config['type']) : 'MYSQL';
		$this->tbp_placeholder = defined('DB_TBP_PLACEHOLDER') ? DB_TBP_PLACEHOLDER : '%tbp';
		$this->key = $key;
		$this->non_access_log_tables = defined('DB_NOT_ACCESS_LOG_TABLES') ? json_decode(DB_NOT_ACCESS_LOG_TABLES, true) : array();
		$this->split_tables = defined('DB_SPLIT_TABLES') ? json_decode(DB_SPLIT_TABLES, true) : array();
		//从数据库
		if ($this->key=='db_master' && defined('DB_SLAVE') && is_array(DB_SLAVE) && count((array)DB_SLAVE)) $this->dbslave = SQL::connect(json_decode(DB_SLAVE, true));
		switch ($this->dbtype) {
			case 'PDO':
				$this->origin_is_pdo = true;
				$this->is_pdo = true;
				break;
			case 'SQLITE':
				$this->origin_is_sqlite3 = true;
				$this->is_sqlite3 = true;
				break;
			default:
				//获取MYSQL的数据库引擎默认值
				$tables = $this->db->get_results("SHOW VARIABLES LIKE '%default_storage_engine%'");
				$this->dbtype = strtoupper($tables[0]->Value);
		}
		$this->origin_tbp = $this->tbp;
		$this->origin_dbtype = $this->dbtype;
		if (defined('IS_SAAS') && IS_SAAS) {
			global $client_id;
			$this->client_id = isset($client_id) ? $client_id : 0;
			//是否op目录
			$this->is_operator_platform = false;
			if ((defined('IS_AG') && IS_AG) || (defined('IS_OP') && IS_OP)) $this->is_operator_platform = true;
			$this->non_client_tables = defined('DB_NON_CLIENT_TABLES') ? json_decode(DB_NON_CLIENT_TABLES, true) : array();
			//如果op目录设置了$_SESSION['client_id']即代表操作指定客户, 所有符合的表增加client_id条件
			$appoint_client_id = isset($_GET['appoint_client_id']) ? intval($_GET['appoint_client_id']) : 0;
			if ($appoint_client_id>0) $_SESSION['appoint_client_id'] = $appoint_client_id;
			$appoint_client_id = isset($_SESSION['appoint_client_id']) ? intval($_SESSION['appoint_client_id']) : 0;
			if ($appoint_client_id>0) {
				$this->client_id = $appoint_client_id;
				$this->is_operator_platform = false;
			}
		}
	}
	//连接数据库
	public static function connect($db_config) {
		if (is_string($db_config)) {
			global $config;
			if (!isset($config[$db_config])) die("Missing '{$db_config}' config!");
			$db_config = $config[$db_config];
		}
		$db_host = isset($db_config['host']) ? $db_config['host'] : 'localhost';
		$db_name = isset($db_config['name']) ? $db_config['name'] : '';
		$db_user = isset($db_config['user']) ? $db_config['user'] : 'root';
		$db_password = isset($db_config['password']) ? $db_config['password'] : '';
		$db_encoding = isset($db_config['encoding']) ? $db_config['encoding'] : 'utf8mb4';
		$db_type = isset($db_config['type']) ? strtoupper($db_config['type']) : 'MYSQL';
		switch ($db_type) {
			case 'MYSQL':case 'MYSQLI':$db_type = 'MYSQL';break;
			case 'SQLITE':case 'SQLITE3':$db_type = 'SQLITE';break;
		}
		switch ($db_type) {
			case 'PDO':
				return new ezSQL_pdo("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_password, $db_encoding);
				break;
			case 'SQLITE':
				$path = '/console';
				if (defined('IS_SAAS') && IS_SAAS) {
					global $client_id;
					$path = (defined('EXTEND_PATH')?EXTEND_PATH:'/console') . ($client_id>0?"/{$client_id}":'');
				}
				$dbpath = (isset($db_config['host']) && strlen($db_config['host'])) ? $db_config['host'] : ROOT_PATH . "{$path}/db/";
				$dbname = (isset($db_config['name']) && strlen($db_config['name'])) ? $db_config['name'] : 'db.sqlite';
				SQL::makedir($dbpath);
				return new ezSQL_sqlite3($dbpath, $dbname);
				break;
			default:
				$ezSQL_mysql = 'ezSQL_mysql';
				if (class_exists('mysqli')) $ezSQL_mysql = 'ezSQL_mysqli';
				return new $ezSQL_mysql($db_user, $db_password, $db_name, $db_host, $db_encoding);
		}
	}
	//使用其他数据库进行操作, SQL::database('table', 'db1')->find();
	public static function database($table, $db_config) {
		$key = '';
		if (is_string($db_config)) {
			global $config;
			if (!isset($config[$db_config])) die("Missing '{$db_config}' config!");
			$key = $db_config;
			$db_config = $config[$db_config];
		}
		if ($db_config instanceof ezSQLcore) {
			global $tbp;
			$db = $db_config;
			if (!strlen($key)) $key = $db->dbhost.'_'.$db->dbname;
			$db_config = array(
				'host' => $db->dbhost,
				'user' => $db->dbuser,
				'name' => $db->dbname,
				'encoding' => $db->encoding,
				'prefix' => $tbp,
				'type' => strtoupper(str_replace('ezSQL_', '', get_class($db)))
			);
		} else {
			if (!strlen($key)) $key = $db_config['host'].'_'.$db_config['name'];
			$db = SQL::connect($db_config);
		}
		return SQL::share($table, $key, $db, $db_config);
	}
	//创建子项表达式实例
	public static function raw($expression) {
		return new SQL_RAW($expression);
	}
	//创建单例
	public static function share($table='', $key='db_master', $database=NULL, $db_config=array()) {
		if (!isset(self::$DB[$key]) || !(self::$DB[$key] instanceof SQL)) {
			global $db, $config;
			if (!$database) $database = $db;
			if (empty($db_config)) $db_config = $config['db_master'];
			$instance = self::$DB[$key] = new self($database, $key, $db_config);
		} else {
			$instance = self::$DB[$key];
		}
		if (!isset(self::$redis[$key]) && !isset(self::$memcached[$key]) && (class_exists('Redis') || class_exists('Memcached')) && (function_exists('redisd') || function_exists('mcached'))) {
			if (!isset(self::$redis[$key]) && class_exists('Redis') && function_exists('redisd')) {
				$setting = defined('REDIS_SETTING') ? json_decode(REDIS_SETTING, true) : array();
				self::$redis[$key] = count($setting) ? redisd($setting['host'], $setting['port']) : redisd();
			}
			if (!isset(self::$redis[$key]) && !isset(self::$memcached[$key]) && class_exists('Memcached') && function_exists('mcached')) {
				$setting = defined('MEMCACHED_SETTING') ? json_decode(MEMCACHED_SETTING, true) : array();
				self::$memcached[$key] = count($setting) ? mcached($setting['host'], $setting['port']) : mcached();
			}
		}
		if (strlen($table)) {
			if (substr_count($table, ' ') > 1) return $instance->query($table); //直接使用自执行SQL, 如 SQL::share("UPDATE article SET title='TITLE' WHERE id=1");
			preg_match('/^~([^:@]+)(:([^@]+))?(@(\w*))?$/', $table, $matcher);
			if (count($matcher)) { //快速使用sqlite3, ~[路径]数据库文件名[:数据表[ 别名]][@[表前缀]], 如 (~/home/db.sqlite:table@tbp_) (~db:table t@tbp_) (~db:table) (~db:table@) (~db@tbp_) (~db@) (~db)
				$instance->origin_db = $instance->db;
				$instance->origin_tbp = $instance->tbp;
				$instance->origin_dbtype = $instance->dbtype;
				$instance->origin_is_sqlite3 = $instance->is_sqlite3;
				$instance->origin_is_pdo = $instance->is_pdo;
				$instance->is_sqlite3 = true;
				$instance->is_pdo = false;
				$host = '';
				$name = $matcher[1];
				if (substr($name, 0, 1) == '/') {
					$host = (dirname($name) && dirname($name)!='.') ? dirname($name).'/' : '';
					$name = trim(str_replace($host, '', $name), '/');
				}
				$table = count($matcher)>3 ? $matcher[3] : '';
				if (count($matcher)>5) $instance->tbp = $matcher[5];
				$type = $instance->dbtype = 'SQLITE';
				$instance->db = SQL::connect(compact('host', 'name', 'type'));
			} else {
				if ($instance->origin_db) $instance->db = $instance->origin_db;
				$instance->tbp = $instance->origin_tbp;
				$instance->dbtype = $instance->origin_dbtype;
				$instance->is_sqlite3 = $instance->origin_is_sqlite3;
				$instance->is_pdo = $instance->origin_is_pdo;
			}
			if (strlen($table)) $instance->from($table);
		}
		return $instance;
	}
	//指定表名, 可设置别名, 如 from('table t')
	public function from($table) {
		return $this->name($table);
	}
	public function name($table) {
		return $this->table($table, $this->tbp);
	}
	//指定表名与表前缀
	public function table($table, $tbp='') {
		$restore = true;
		if (is_string($table) && preg_match('/^!\w+/', $table)) { //表名前加!代表不restore
			$restore = false;
			$table = substr($table, 1);
		}
		if ($restore) $this->restore();
		if (is_string($table) && strlen($table)) $this->table = $tbp.$table;
		return $this;
	}
	//左联接
	public function left($table, $on='') {
		$sql = " LEFT JOIN {$this->tbp}{$table}";
		if (strlen($on)) $sql .= " ON {$on}";
		$this->left[] = $sql;
		return $this;
	}
	//右联接
	public function right($table, $on='') {
		$sql = " RIGHT JOIN {$this->tbp}{$table}";
		if (strlen($on)) $sql .= " ON {$on}";
		$this->right[] = $sql;
		return $this;
	}
	//等值联接
	public function inner($table, $on='') {
		$sql = " INNER JOIN {$this->tbp}{$table}";
		if (strlen($on)) $sql .= " ON {$on}";
		$this->inner[] = $sql;
		return $this;
	}
	//多联接
	public function cross($table) {
		$this->cross[] = ",{$this->tbp}{$table}";
		return $this;
	}
	//联合查询, 多表不相同字段混合查询, 共有字段名需使用$fields映射, 如 'id', 'title'=>'name', 'memo'=>"'MEMO'", 'content'=>'', 'status'=>'0' 格式化为 id, name as title, 'MEMO' as memo, content, 0 as status
	//SQL::share('article')->union('video', ['id', 'title'=>'name', 'memo'=>"''", 'content'=>'', 'status'=>0])->where("title='TITLE'")->sort('id DESC')->find('id, title, content, status')
	public function union($table, $fields) {
		$this->union[] = $this->tbp.$table;
		$this->unionFields[] = $fields;
		return $this;
	}
	//查询条件
	//$where为数组时value需要指定逻辑关系, 如 ->where(['name' => "='keyword'", 'id' => '=1'])->where(['name' => "LIKE '%keyword%'"])->where(['age' => '>10'])
	//可指定OR值, 如 ->where([ 'id' => ['>1', '<10'], 'or' => [['name' => "='keyword1'"], ['name' => "='keyword2'"]] ])
	//快捷查询, 如 ->where([ 'id&status' => '>0' ])->where([ 'id|status' => '>0' ])
	//或者使用子项表达式, 如 ->where([ 'name' => "='keyword1'", SQL::raw("(id=1 OR id=2)") ])
	public function where($where) {
		if (!is_array($where)) {
			if (strtoupper(substr(strlen($where), 0, 4)) == 'AND ') $this->where .= $where;
			else $this->where .= (strlen($this->where)?' AND ':'') . $where;
		} else {
			$wheres = array();
			$makeupCall = function($key, $value) use (&$makeupCall) {
				if (is_array($value)) {
					$items = array();
					if (strtoupper($key) == 'OR') {
						foreach ($value as $ke=>$val) {
							if (is_numeric($ke)) {
								foreach ($val as $_ke=>$_val) $items[] = $makeupCall($_ke, $_val);
							} else {
								$items[] = $makeupCall($ke, $val);
							}
						}
					} else {
						$k = $key;
						if (strpos($key, '`')===false) $k = "`{$key}`";
						foreach ($value as $val) $items[] = "{$k}{$val}";
					}
					$s = count($items)==1 ? implode(' OR ', $items) : '('.implode(' OR ', $items).')';
				} else if (preg_match('/[&|]/', $key)) {
					$items = array();
					if (strpos($key, '&') !== false) {
						$ks = explode('&', $key);
						foreach ($ks as $k) $items[] = $makeupCall($k, $value);
					} else {
						$ks = explode('|', $key);
						$val = array();
						foreach ($ks as $k) $val[] = array("{$k}" => $value);
						$items[] = $makeupCall('OR', $val);
					}
					$s = implode(' AND ', $items);
				} else if ($value instanceof SQL_RAW) {
					$s = $value->expression;
				} else {
					$k = $key;
					if (strpos($key, '`')===false) $k = "`{$key}`";
					if (!$this->is_pdo) {
						$s = "{$k}{$value}";
					} else {
						$pdoParamCount = count($this->whereParam);
						$s = "{$k}:{$key}{$pdoParamCount}";
						$this->whereParam[":{$key}{$pdoParamCount}"] = $value;
					}
				}
				return $s;
			};
			foreach ($where as $key=>$value) {
				$wheres[] = $makeupCall($key, $value);
			}
			$this->where .= (strlen($this->where)?' AND ':'') . implode(' AND ', $wheres);
		}
		return $this;
	}
	//去重查询
	public function distinct($field) {
		if (strlen($field)) $this->distincter = "DISTINCT({$field})";
		return $this;
	}
	//时间对比查询, ->whereTime('d', 'add_time', '<1') //查询add_time小于1天的记录
	public function whereTime($interval, $field, $operatorAndValue, $now='') {
		return $this->compareTime($interval, $field, $operatorAndValue, $now);
	}
	public function compareTime($interval, $field, $operatorAndValue, $now='') {
		switch ($interval) {
			case 'y':$interval = 'YEAR';break;
			case 'q':$interval = 'QUARTER';break;
			case 'm':$interval = 'MONTH';break;
			case 'w':$interval = 'WEEK';break;
			case 'd':$interval = 'DAY';break;
			case 'h':$interval = 'HOUR';break;
			case 'n':$interval = 'MINUTE';break;
			case 's':$interval = 'SECOND';break;
		}
		$interval = strtoupper($interval);
		$his = '';
		if ($interval=='HOUR') $his = ' %H';
		else if ($interval=='MINUTE') $his = ' %H:%i';
		else if ($interval=='SECOND') $his = ' %H:%i:%s';
		if (!strlen($now)) {
			if (!strlen($his)) $now = "DATE_FORMAT(NOW(),'%Y-%m-%d')";
			else {
				if ($interval=='HOUR') $now = "DATE_FORMAT(NOW(),'%Y-%m-%d %H')";
				else if ($interval=='MINUTE') $now = "DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i')";
				else $now = 'NOW()';
			}
		}
		//$fieldOpe = "IF(ISNUMERIC({$field}),FROM_UNIXTIME({$field},'%Y-%m-%d{$his}'),{$field})";
		$fieldOpe = "FROM_UNIXTIME({$field},'%Y-%m-%d{$his}')";
		$this->where .= (strlen($this->where)?' AND ':'') . "TIMESTAMPDIFF({$interval},{$fieldOpe},{$now}){$operatorAndValue}";
		return $this;
	}
	//LIKE查询, 如 name LIKE 'G_ARTICLE/_%' ESCAPE '/'
	public function like($field, $str, $escape='') {
		$where = "{$field} LIKE '{$str}'";
		if (strlen($escape)) $where .= " ESCAPE '{$escape}'";
		$this->where .= (strlen($this->where)?' AND ':'') . $where;
		return $this;
	}
	//分组(聚合)
	public function group($group) {
		$this->group = strlen($group) ? "GROUP BY {$group}" : '';
		return $this;
	}
	//聚合筛选, 语法与where一样
	public function having($having) {
		$this->having = strlen($having) ? "HAVING {$having}" : '';
		return $this;
	}
	//排序
	public function sort($sort) {
		return $this->order($sort);
	}
	public function order($order) {
		$this->order = strlen($order) ? "ORDER BY {$order}" : '';
		return $this;
	}
	//ezr分页模式
	public function isezr($isezr=true) {
		if ($this->ezr) {
			if (is_array($isezr)) {
				$this->isezr = true;
				$this->setpages($isezr);
			} else {
				$this->isezr = $isezr;
			}
		}
		return $this;
	}
	//ezr分页模式时分页的首页html
	public function firstpage($firstpage='') {
		$this->firstpage = $firstpage;
		return $this;
	}
	//ezr分页模式时分页的上一页html
	public function prevpage($prevpage='') {
		$this->prevpage = $prevpage;
		return $this;
	}
	//ezr分页模式时分页的下一页html
	public function nextpage($nextpage='') {
		$this->nextpage = $nextpage;
		return $this;
	}
	//ezr分页模式时分页的尾页html
	public function lastpage($lastpage='') {
		$this->lastpage = $lastpage;
		return $this;
	}
	//ezr分页模式时显示跳转页码控件
	public function isJumppage($isJumppage=true) {
		$this->isJumppage = $isJumppage;
		return $this;
	}
	//ezr分页模式时重写分页链接,一般伪静态用,[P]将替换为页数
	public function rewritePage($rewritePage='') {
		$this->rewritePage = $rewritePage;
		return $this;
	}
	//记录偏移量
	public function offset($offset) {
		if (is_numeric($offset)) $this->offset = intval($offset);
		return $this;
	}
	//返回记录最大数目
	public function pagesize($pagesize) {
		if (is_numeric($pagesize)) $this->pagesize = intval($pagesize);
		return $this;
	}
	//设定记录偏移量与返回记录最大数目
	public function limit($offset, $pagesize) {
		return $this->offset($offset)->pagesize($pagesize);
	}
	//使用缓存查询结果,0不缓存,-1永久缓存,>0缓存时间(单位秒)
	public function cached($cached=0) {
		$this->cached = $cached;
		return $this;
	}
	//插入/更新/删除返回操作的记录
	public function returnObj() {
		$this->returnObj = true;
		return $this;
	}
	//查询多行记录且只有一个字段时直接转为无索引数组,glue不为空时把数组使用glue合并为字符串
	public function returnArray($glue='') {
		$this->returnArray = true;
		$this->glue = $glue;
		return $this;
	}
	//不自动增加client_id条件
	public function skipClient() {
		$this->skipClient = true;
		return $this;
	}
	//生成用encrypt_param加密的网址字段,例如?app=home&act=index&id=[id],[id]将替换为记录集里面对应的字段值
	public function href($href) {
		$this->href = $href;
		return $this;
	}
	//不执行sql
	public function debug() {
		$this->debug = true;
		return $this;
	}
	//生命周期内把运行过的sql记录到log
	public function log() {
		$this->log = true;
		return $this;
	}
	//设置分页参数
	public function setpages($data=array()) {
		$BRSR = isset($_GET['BRSR']) ? $_GET['BRSR'] : '';
		if ($this->smarty) $this->smarty->assign('BRSR', $BRSR);
		if ($this->ezr) {
			$this->ezr->set_qs_val('app', isset($_REQUEST['app']) ? trim($_REQUEST['app']) : '');
			$this->ezr->set_qs_val('act', isset($_REQUEST['act']) ? trim($_REQUEST['act']) : '');
		}
		$this->pagelink = '';
		foreach ($data as $key=>$value) {
			if ($this->smarty) $this->smarty->assign($key, $value);
			if ($this->ezr) $this->ezr->set_qs_val($key, $value);
			$this->pagelink .= "&{$key}={$value}";
		}
		return $this;
	}
	//记录数量
	public function count() {
		return intval($this->value());
	}
	//记录是否存在
	public function exist() {
		return $this->count() > 0;
	}
	//查询字段总和
	public function sum($field, $type='intval') {
		if (!strlen($type)) return NULL;
		return $type($this->value("SUM({$field})"));
	}
	//查询字段平均值
	public function avg($field, $type='intval') {
		if (!strlen($type)) return NULL;
		return $type($this->value("AVG({$field})"));
	}
	//查询字段最小值
	public function min($field, $type='intval') {
		if (!strlen($type)) return NULL;
		return $type($this->value("MIN({$field})"));
	}
	//查询字段最大值
	public function max($field, $type='intval') {
		if (!strlen($type)) return NULL;
		return $type($this->value("MAX({$field})"));
	}
	//查询字段
	public function value($field='', $type='') {
		if (!strlen($field)) return intval($this->isezr(false)->pagesize(-1)->find('COUNT(*)'));
		$val = $this->isezr(false)->pagesize(-1)->find($field);
		if ($field == 'id') { //默认id为整数型
			$val = intval($val);
		} else if (strlen($type)) {
			$val = $type($val);
		}
		return $val;
	}
	//查询某列值
	public function column($field, $columns_type=array()) {
		return $this->isezr(false)->returnArray()->find($field, $columns_type);
	}
	//查询单条记录
	public function row($field='*', $columns_type=array()) {
		return $this->isezr(false)->pagesize(1)->find($field, $columns_type);
	}
	//更新字段
	public function setField($field, $value='') {
		return $this->update(array("{$field}"=>$value));
	}
	//字段递增
	public function setInc($field, $step=1) {
		if (preg_match('/^\d+$/', strval($step))) $step = "+{$step}";
		return $this->setField($field, array($step));
	}
	//字段递减
	public function setDec($field, $step=1) {
		return $this->setInc($field, "-{$step}");
	}
	//查询, fields可为闭包,接收一个参数:$this,可设置返回值:筛选字段, columns_type字段类型:['intval'=>['id', 'add_time'], 'floatval'=>['price']]
	//随机10条数据
	//SELECT t1.* FROM `table` t1 INNER JOIN (SELECT (MIN(t2.id) + ROUND(RAND()*(MAX(t2.id) - MIN(t2.id)))) AS id FROM `table` t2 WHERE t2.`level`=1) AS t ON t1.id>=t.id LIMIT 10;
	//SELECT * FROM `table` t1 JOIN (SELECT ROUND( RAND()*((SELECT MAX(id) FROM `table`)-(SELECT MIN(id) FROM `table`)) + (SELECT MIN(id) FROM `table`) ) AS id) t2 WHERE t1.id>=t2.id ORDER BY t1.id LIMIT 10
	//SELECT * FROM `table` ORDER BY RAND() LIMIT 10
	public function find($fields='*', $columns_type=array()) {
		return $this->select($fields, $columns_type);
	}
	public function select($fields='*', $columns_type=array()) {
		if (!is_string($fields) && is_callable($fields)) {
			$return = $fields($this);
			$fields = (is_string($return) || is_array($return)) ? $return : '*';
		}
		$res = NULL;
		$sql = $this->createSql($fields);
		if ($this->log) SQL::write_log($sql);
		$this->sql = $sql;
		$this->sqls[] = $sql;
		if ($this->cached!=0) {
			$res = $this->_cacheSql($sql);
			if (!is_null($res)) return $res;
		}
		try {
			if ($this->isezr) {
				if (!$this->debug) {
					if ($this->ezr) {
						if ($this->pagesize>0) $this->ezr->num_results_per_page = $this->pagesize;
						$this->ezr->set_name_first($this->firstpage);
						$this->ezr->set_name_prev($this->prevpage);
						$this->ezr->set_name_next($this->nextpage);
						$this->ezr->set_name_last($this->lastpage);
						$this->ezr->is_show_jump_page = $this->isJumppage;
						$this->ezr->rewrite_page = $this->rewritePage;
						$res = $this->ezr->get_results($sql, $this->whereParam, $this->dbslave ? $this->dbslave : $this->db);
						$res = json_decode(json_encode($res, JSON_UNESCAPED_UNICODE)); //数据结构保持与$db一致
						if (count($columns_type)) $res = $this->_columnsType($res, $columns_type);
						$this->page = $this->ezr->get_navigation();
						if ($this->smarty) {
							$this->smarty->assign('sharepage', $this->page);
							$this->smarty->assign('sharepages', $this->ezr->get_navigations());
						}
					} else {
						$res = array();
					}
				}
			} else {
				if ($this->pagesize<0) {
					if (!$this->debug) $res = $this->dbslave ? $this->dbslave->get_var($sql, $this->whereParam) : $this->db->get_var($sql, $this->whereParam);
				} else if ($this->pagesize==0) {
					if (!$this->debug) $res = $this->dbslave ? $this->dbslave->get_results($sql, $this->whereParam) : $this->db->get_results($sql, $this->whereParam);
					if (count($columns_type)) $res = $this->_columnsType($res, $columns_type);
					if ($this->returnArray) {
						if ($res) {
							$keys = array_keys(get_object_vars($res[0]));
							$res = count($keys)==1 ? array_column($res, $keys[0]) : json_decode(json_encode($res, JSON_UNESCAPED_UNICODE), true);
						} else {
							$res = array();
						}
						if (strlen($this->glue)) $res = implode($this->glue, $res);
					}
				} else if ($this->pagesize==1) {
					if (!$this->debug) $res = $this->dbslave ? $this->dbslave->get_row($sql, $this->whereParam) : $this->db->get_row($sql, $this->whereParam);
					if (count($columns_type)) $res = $this->_columnsType($res, $columns_type);
					if ($this->returnArray) {
						$res = $res ? json_decode(json_encode($res, JSON_UNESCAPED_UNICODE), true) : array();
						if (strlen($this->glue)) $res = implode($this->glue, $res);
					}
				} else {
					if (!$this->debug) $res = $this->dbslave ? $this->dbslave->get_results($sql, $this->whereParam) : $this->db->get_results($sql, $this->whereParam);
					if (count($columns_type)) $res = $this->_columnsType($res, $columns_type);
					if ($this->returnArray) {
						if ($res) {
							$keys = array_keys(get_object_vars($res[0]));
							$res = count($keys)==1 ? array_column($res, $keys[0]) : json_decode(json_encode($res, JSON_UNESCAPED_UNICODE), true);
						} else {
							$res = array();
						}
						if (strlen($this->glue)) $res = implode($this->glue, $res);
					}
				}
			}
		} catch (Exception $e) {
			$ex = new Exception;
			$trace = $sql."\n".$e->getMessage()."\n".$ex->getTraceAsString();
			defined('ERROR_FILE') && SQL::write_log($trace, ERROR_FILE);
			throw new RuntimeException("SQL Error: {$e}");
		}
		$this->getPDOError();
		if (strlen($this->href) && defined('CRYPT_KEY') && function_exists('encrypt_param')) {
			if (is_object($res)) {
				$href = preg_replace_callback('/\[(\w+)]/', function($matches) use ($res) {
					return $res->{$matches[1]};
				}, $this->href);
				$res->href = encrypt_param($href, CRYPT_KEY, 'wap');
			} else if (is_array($res) && count($res) && is_object($res[0])) {
				foreach ($res as $k=>$r) {
					$href = preg_replace_callback('/\[(\w+)]/', function($matches) use ($r) {
						return $r->{$matches[1]};
					}, $this->href);
					$res[$k]->href = encrypt_param($href, CRYPT_KEY, 'wap');
				}
			}
		}
		if ($this->cached!=0 && !is_null($res)) $this->_cacheSql($sql, $res);
		$this->wherebase64 = base64_encode($this->where);
		$this->sql_count++;
		return $res;
	}
	//创建SQL语句
	public function createSql($fields='*') {
		if (is_array($fields)) { //['别名'=>'字段名1', '字段名2', '字段名3'=>"'YES'", '字段名4'=>'0', '字段名5'=>"''", '字段名6'=>''] = "字段名1 as 别名, 字段名2, 'YES' as 字段名3, 0 as 字段名4, '' as 字段名5, 字段名6"
			$fieldArray = array();
			foreach ($fields as $k=>$v) {
				if (is_numeric($k)) $fieldArray[] = $v;
				else $fieldArray[] = strlen($v) ? "{$v} as {$k}" : $k;
			}
			$fields = implode(', ', $fieldArray);
		} else if (preg_match('/^\w+(\|\w+)+$/', $fields)) { //排除不需要的字段, ->find('id|content')
			$fields = explode('|', $fields);
			$fieldArray = array();
			$desc = $this->dbslave ? $this->dbslave->get_results("SHOW COLUMNS FROM {$this->table}") : $this->db->get_results("SHOW COLUMNS FROM {$this->table}");
			$this->sql_count++;
			foreach ($desc as $g) {
				if (!in_array($g->Field, $fields)) $fieldArray[] = $g->Field;
			}
			$fields = implode(', ', $fieldArray);
		} else {
			if (!strlen($fields)) {
				if (!strlen($this->distincter)) $fields = '*';
				else $fields = $this->distincter;
			} else if (strlen($this->distincter)) {
				if (trim($fields)!='*') $fields = "{$this->distincter}, {$fields}";
				else $fields = $this->distincter;
			}
		}
		$isHundredThousandOffset = false;
		$tableLong = $alias = $this->table;
		if (stripos($alias, ' ')!==false) {
			$alias = preg_split('/\s+/', $alias);
			$tableLong = $alias[0];
			$alias = $alias[1];
		}
		$is_client_table = (defined('IS_SAAS') && IS_SAAS && !$this->is_sqlite3 && !$this->skipClient && !$this->is_operator_platform && !in_array(str_replace($this->tbp, '', $tableLong), $this->non_client_tables));
		$whereFunction = function($alias) use ($is_client_table) {
			$sql = '';
			if (is_numeric($this->where)) { //默认是id
				$sql .= " WHERE ";
				if ($is_client_table) {
					if (!$this->is_pdo) {
						$sql .= "{$alias}.client_id='{$this->client_id}' AND ";
					} else {
						$sql .= "{$alias}.client_id=:client_id AND ";
						$this->whereParam[':client_id'] = $this->client_id;
					}
				}
				if (!$this->is_pdo) {
					$sql .= "{$alias}.id='{$this->where}'";
				} else {
					$sql .= "{$alias}.id=:id";
					$this->whereParam[':id'] = $this->where;
				}
			} else {
				if ($is_client_table || strlen(trim($this->where))) {
					$sql .= " WHERE ";
					if ($is_client_table) {
						if (!$this->is_pdo) {
							$sql .= "{$alias}.client_id='{$this->client_id}'";
						} else {
							$sql .= "{$alias}.client_id=:client_id";
							$this->whereParam[':client_id'] = $this->client_id;
						}
					}
					if (strlen(trim($this->where))) {
						if ($is_client_table) {
							if (strtoupper(substr(trim($this->where), 0, 4)) == 'AND ') $sql .= ' '.trim($this->where);
							else $sql .= ' AND '.trim($this->where);
						} else {
							if (strtoupper(substr(trim($this->where), 0, 4)) == 'AND ') {
								$sql .= ' '.substr(trim($this->where), 4);
							} else {
								$sql .= ' '.trim($this->where);
							}
						}
					}
				}
			}
			return $sql;
		};
		if (count($this->union) && count($this->union)==count($this->unionFields)) {
			$sql = "(SELECT {$fields} FROM {$this->table}" . $whereFunction($alias) . ")";
			for ($i=0; $i<count($this->union); $i++) {
				$union = $this->union[$i];
				$f = $this->unionFields[$i];
				$field = array();
				foreach ($f as $k=>$v) {
					if (is_numeric($k)) $field[] = $v;
					else $field[] = strlen($v) ? "{$v} as {$k}" : $k;
				}
				$field = implode(', ', $field);
				$sql .= " UNION ALL (SELECT {$field} FROM {$union}" . $whereFunction($alias) . ")";
			}
		} else {
			//偏移量超十万的优化
			$isHundredThousandOffset = !is_numeric($this->where) && !count($this->left) && !count($this->right) && !count($this->inner) && !count($this->cross) && !$this->isezr && $this->pagesize>1 && $this->offset>=100000;
			$inner = '';
			if ($isHundredThousandOffset) {
				$_alias = 'tmp1';
				$inner = " INNER JOIN (SELECT id FROM {$tableLong}";
				if (stripos($this->table, ' ')!==false) {
					$_alias = $alias;
				} else {
					$inner = " {$_alias} {$inner}";
				}
				$inner .= preg_replace('/\b'.$_alias.'\./', '', $whereFunction($_alias));
				if (!is_numeric($this->where)) {
					if (strlen(trim($this->group))) $inner .= ' '.preg_replace('/\b'.$_alias.'\./', '', $this->group);
					if (strlen(trim($this->having))) $inner .= ' '.preg_replace('/\b'.$_alias.'\./', '', $this->having);
					if (strlen(trim($this->order))) $inner .= ' '.preg_replace('/\b'.$_alias.'\./', '', $this->order);
				}
				$inner .= " LIMIT {$this->offset},{$this->pagesize}) tmp2";
				$inner .= " ON {$_alias}.id=tmp2.id";
				$fields = explode(',', $fields);
				$fields = array_map(function($field) use ($_alias) {
					$field = trim($field);
					if (!preg_match('/^\w+\./', $field)) $field = $_alias.'.'.$field;
					return $field;
				}, $fields);
				$fields = implode(', ', $fields);
			}
			$sql = "SELECT {$fields} FROM {$this->table}{$inner}";
			foreach ($this->left as $subtable) $sql .= $subtable;
			foreach ($this->right as $subtable) $sql .= $subtable;
			foreach ($this->inner as $subtable) $sql .= $subtable;
			foreach ($this->cross as $subtable) $sql .= $subtable;
			if (!$isHundredThousandOffset) $sql .= $whereFunction($alias);
		}
		if (!is_numeric($this->where)) {
			if (strlen(trim($this->group)) && !$isHundredThousandOffset) $sql .= " {$this->group}";
			if (strlen(trim($this->having)) && !$isHundredThousandOffset) $sql .= " {$this->having}";
			if (strlen(trim($this->order))) $sql .= " {$this->order}";
			if (!$this->isezr && $this->pagesize>1 && !$isHundredThousandOffset) $sql .= " LIMIT {$this->offset},{$this->pagesize}";
		}
		$sql = str_replace($this->tbp_placeholder, $this->tbp, $sql);
		$sql = preg_replace_callback('/(__\w+__)/', function($match) {
			return $this->tbp.strtolower($match[1]);
		}, $sql);
		return $sql;
	}
	//插入记录, array_key:按data[array_key]的值(是一个数组)的数量来循环增加VALUES
	public function insert($data, $array_key='') {
		if ($this->is_pdo) {
			if (!is_array($data) || !count($data)) return false;
			$array_key = '';
		}
		if (!is_array($data) && !strlen(trim($data))) return false;
		$tableLong = $alias = $this->table;
		if (stripos($alias, ' ')!==false) {
			$alias = preg_split('/\s+/', $alias);
			$tableLong = $alias[0];
			$alias = $alias[1];
		}
		$is_client_table = (defined('IS_SAAS') && IS_SAAS && !$this->is_sqlite3 && !$this->skipClient && !$this->is_operator_platform && !in_array(str_replace($this->tbp, '', $tableLong), $this->non_client_tables));
		$is_table_split = in_array(str_replace($this->tbp, '', $tableLong), $this->split_tables);
		$table = $this->table;
		if ($is_table_split) {
			$t = $this->dbslave ? $this->dbslave->get_row("SHOW TABLE STATUS LIKE '{$tableLong}'") : $this->db->get_row("SHOW TABLE STATUS LIKE '{$tableLong}'");
			if (strtoupper($t->Engine) != 'MRG_MYISAM') {
				$table = "{$tableLong}_0";
				if (!$t->Auto_increment) $t->Auto_increment = 1;
				$this->db->query("ALTER TABLE `{$tableLong}` CHANGE `id` `id` INT(11) NOT NULL");
				$this->db->query("CREATE TABLE `{$table}` LIKE `{$tableLong}`");
				$this->db->query("ALTER TABLE `{$table}` ENGINE=MYISAM");
				$this->db->query("INSERT INTO `{$table}` SELECT * FROM `{$tableLong}`");
				$this->db->query("CREATE TABLE `{$tableLong}_id` (`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE=MYISAM AUTO_INCREMENT={$t->Auto_increment}");
				$this->db->query("INSERT INTO `{$tableLong}_id` SELECT `id` FROM `{$tableLong}`");
				$this->db->query("ALTER TABLE `{$tableLong}` ENGINE=MRG_MYISAM, INSERT_METHOD=LAST, UNION=(`{$table}`)");
				$this->sql_count += 7;
			} else {
				$t = $this->dbslave ? $this->dbslave->get_row("SHOW CREATE TABLE `{$tableLong}`") : $this->db->get_row("SHOW CREATE TABLE `{$tableLong}`");
				$t = json_decode(json_encode($t, JSON_UNESCAPED_UNICODE), true);
				preg_match('/UNION=\(([^)]+?)\)/i', $t['Create Table'], $matcher);
				$tables = explode(',', str_replace('`', '', $matcher[1]));
				$table = $tables[count($tables)-1];
				$this->sql_count++;
				$count = $this->dbslave ? $this->dbslave->get_var("SELECT COUNT(*) FROM `{$table}`") : $this->db->get_var("SELECT COUNT(*) FROM `{$table}`");
				if ($count >= 100*10000) {
					$t = explode('_', $table);
					$n = intval($t[count($t)-1]) + 1;
					$this->db->query("CREATE TABLE `{$tableLong}_{$n}` LIKE `{$table}`");
					$this->sql_count++;
					$table = "{$tableLong}_{$n}";
					$tables[] = $table;
					$this->db->query("ALTER TABLE `{$tableLong}` ENGINE=MRG_MYISAM, INSERT_METHOD=LAST, UNION=(`".implode('`,`', $tables)."`)");
					$this->sql_count++;
				}
			}
			$this->sql_count++;
		}
		$sql = "INSERT INTO {$table}";
		if (is_array($data)) {
			if (array_values($data) === $data) { //数字索引, 如 [{name:'name1', price:0.01}, {name:'name2', price:0.01}]
				$fields = array();
				$values = array();
				if ($is_table_split) $fields[] = "`id`";
				$d = $data[0];
				foreach ($d as $key => $value) {
					if (strpos($key, '`')===false) $key = "`{$key}`";
					$fields[] = $key;
				}
				if ($is_client_table) $fields[] = "`client_id`";
				$sql .= " (".implode(', ', $fields).") VALUES";
				foreach ($data as $k => $d) {
					$val = array();
					if ($is_table_split) {
						$split_id = $this->query("INSERT INTO `{$tableLong}_id` (id) values ('0')", true);
						$val[] = "'{$split_id}'";
					}
					if ($is_client_table) $d['client_id'] = $this->client_id;
					foreach ($d as $key => $value) {
						$val[] = $this->_escape($value);
					}
					$values[] = "(".implode(', ', $val).")";
				}
				$sql .= ' '.implode(', ', $values);
			} else { //键名索引, 如 [name:['name1', 'name2'], price:['0.01', '0.01']]
				if ($is_client_table) {
					if (!$this->is_pdo) {
						$data['client_id'] = $this->client_id;
					} else {
						$this->setParam[':client_id'] = $this->client_id;
					}
				}
				$array_value = NULL;
				if (strlen($array_key)) {
					$array_value = isset($data[$array_key]) ? $data[$array_key] : NULL;
					if (!is_array($array_value)) $array_key = '';
				}
				$fields = array();
				$values = array();
				if (strlen($array_key)) { //多条插入
					if ($is_table_split) $fields[] = "`id`";
					foreach ($data as $key => $value) {
						if (strpos($key, '`')===false) $key = "`{$key}`";
						$fields[] = $key;
					}
					$sql .= " (".implode(', ', $fields).") VALUES";
					$array_values = array_values($data);
					for ($i=0; $i<count($array_value); $i++) {
						if (!strlen(trim($array_value[$i]))) continue;
						$val = array();
						if ($is_table_split) {
							$split_id = $this->query("INSERT INTO `{$tableLong}_id` (id) values ('0')", true);
							$val[] = "'{$split_id}'";
						}
						for ($j=0; $j<count($array_values); $j++) {
							if (is_array($array_values[$j])) {
								$val[] = isset($array_values[$j][$i]) ? $this->_escape($array_values[$j][$i]) : "''";
							} else {
								$val[] = $this->_escape($array_values[$j]);
							}
						}
						$values[] = "(".implode(', ', $val).")";
					}
					$sql .= ' '.implode(', ', $values);
				} else { //单条插入
					if ($is_table_split) {
						$fields[] = "`id`";
						$split_id = $this->query("INSERT INTO `{$tableLong}_id` (id) values ('0')", true);
						if (!$this->is_pdo) {
							$values[] = "'{$split_id}'";
						} else {
							$values[] = ":id";
							$this->setParam[":id"] = $split_id;
						}
					}
					foreach ($data as $k => $value) {
						$key = $k;
						if (strpos($key, '`')===false) $key = "`{$key}`";
						$fields[] = $key;
						if (!$this->is_pdo) {
							$values[] = $this->_escape($value);
						} else {
							$values[] = ":{$k}";
							$this->setParam[":{$k}"] = $value;
						}
					}
					$sql .= " (".implode(', ', $fields).") VALUES (".implode(', ', $values).")";
				}
			}
		} else {
			$sql .= " {$data}";
		}
		$sql = str_replace($this->tbp_placeholder, $this->tbp, $sql);
		$sql = preg_replace_callback('/(__\w+__)/', function($match) {
			return $this->tbp.strtolower($match[1]);
		}, $sql);
		if ($this->log) SQL::write_log($sql);
		$this->sql = $sql;
		$this->sqls[] = $sql;
		$insert_id = 0;
		$last_id = 0;
		if (!$this->debug) {
			try {
				$this->beginTransaction();
				$result = $this->db->query($sql, $this->setParam);
				$this->commit();
			} catch (Exception $e) {
				$this->rollBack();
				$ex = new Exception;
				$trace = $sql."\n".$e->getMessage()."\n".$ex->getTraceAsString();
				defined('ERROR_FILE') && SQL::write_log($trace, ERROR_FILE);
				throw new RuntimeException("SQL Error: {$e}");
			}
			$this->getPDOError();
			if ($result) {
				if ($is_table_split) {
					$insert_id = intval($this->db->get_var("SELECT MAX(id) FROM {$table}"));
					$this->sql_count++;
				} else {
					$insert_id = intval($this->db->insert_id);
				}
				$last_id = $insert_id;
				if ($this->returnObj) {
					$insert_id = $this->db->get_row("SELECT * FROM {$this->table} WHERE id='{$insert_id}'");
					$this->sql_count++;
				}
			} else {
				if ($this->returnObj) $insert_id = NULL;
			}
			if ($last_id && !in_array(str_replace($this->tbp, '', $this->table), $this->non_access_log_tables)) {
				if (defined('DIRNAME') && DIRNAME=='gm' && !$this->is_sqlite3 && strpos($this->table, "{$this->tbp}admin")===false) {
					$sq = "SELECT * FROM {$this->table} WHERE id='{$last_id}'";
					$row = $this->db->get_row($sq);
					$this->sql_count++;
					if ($row) {
						$rowstr = str_replace('","', '", "', json_encode($row, JSON_UNESCAPED_UNICODE));
						$table = $this->dbslave ? $this->dbslave->get_results("SHOW FULL COLUMNS FROM {$this->table}") : $this->db->get_results("SHOW FULL COLUMNS FROM {$this->table}");
						$this->sql_count++;
						$row = json_decode($rowstr, true);
						foreach ($row as $k => $g) {
							foreach ($table as $item) {
								if ($item->Field == $k) {
									if (strlen($item->Comment)) {
										$Comment = str_replace('，', ',', $item->Comment);
										$Comment = explode(',', $Comment);
										$rowstr = str_replace('"'.$k.'":', $Comment[0].':', $rowstr);
									}
									break;
								}
							}
						}
						$user_id = 0;
						$user_name = '';
						if (isset($_SESSION['admin']) && is_object($_SESSION['admin'])) {
							$user_id = $_SESSION['admin']->id;
							$user_name = $_SESSION['admin']->name;
						}
						$t = $this->dbslave ? $this->dbslave->get_row("SHOW TABLE STATUS LIKE '{$this->table}'") : $this->db->get_row("SHOW TABLE STATUS LIKE '{$this->table}'");
						$this->sql_count++;
						$content = "{$user_name}<font color=\"blue\">新增</font>{$t->Comment}<br /><font color=\"gray\">".addslashes($rowstr).'</font>';
						$client_field = (defined('IS_SAAS') && IS_SAAS) ? 'client_id, ' : '';
						$client_value = (defined('IS_SAAS') && IS_SAAS) ? "'{$this->client_id}', " : '';
						$sq = "INSERT INTO {$this->tbp}access_log ({$client_field}user_id, type, content, ip, add_time)
							VALUES ({$client_value}'{$user_id}', '1', '{$content}', '{$this->ip}', '".time()."')";
						$this->db->query($sq);
						$this->sql_count++;
					}
				}
			}
		}
		if ($this->smarty && $this->smarty->caching) {
			$this->smarty->clearAllCache();
		}
		$this->sql_count++;
		return $insert_id;
	}
	//更新记录
	public function update($data) {
		if ($this->is_pdo) {
			if (!is_array($data) || !count($data)) return false;
		}
		if (!is_array($data) && !strlen(trim($data))) return false;
		$tableLong = $alias = $this->table;
		if (stripos($alias, ' ')!==false) {
			$alias = preg_split('/\s+/', $alias);
			$tableLong = $alias[0];
			$alias = $alias[1];
		}
		$is_client_table = (defined('IS_SAAS') && IS_SAAS && !$this->is_sqlite3 && !$this->skipClient && !$this->is_operator_platform && !in_array(str_replace($this->tbp, '', $tableLong), $this->non_client_tables));
		$sql = "UPDATE {$this->table} SET";
		if (is_array($data)) {
			$set = array();
			foreach ($data as $key=>$value) {
				if (strpos($key, '`')===false) $key = "`{$key}`";
				if (is_array($value)) { //处理类似 logins=logins+1 等, 如 'logins'=>['logins', '+1'] 或 'logins'=>['+1'] 自动引用key
					if (count($value) == 1) {
						$value = $key . implode('', $value);
					} else {
						$value = implode('', $value);
					}
				} else if ($value instanceof SQL_RAW) { //处理类似 name=TRIM(name) 等, 如 'name'=>SQL::raw('TRIM(name)')
					$value = $value->expression;
				} else {
					$value = $this->_escape($value);
				}
				if (!$this->is_pdo) {
					$set[] = "{$key}={$value}";
				} else {
					$set[] = "{$key}=:{$key}";
					$this->setParam[":{$key}"] = $value;
				}
			}
			$sql .= ' '.implode(', ', $set);
		} else {
			$sql .= " {$data}";
		}
		$where = '';
		if (is_numeric($this->where)) {
			$sq = " WHERE ";
			if ($is_client_table) {
				if (!$this->is_pdo) {
					$sq .= "{$alias}.client_id='{$this->client_id}' AND ";
				} else {
					$sq .= "{$alias}.client_id=:client_id AND ";
					$this->whereParam[':client_id'] = $this->client_id;
				}
			}
			if (!$this->is_pdo) {
				$sq .= "{$alias}.id='{$this->where}'";
			} else {
				$sq .= "{$alias}.id=:id";
				$this->whereParam[':id'] = $this->where;
			}
			$sql .= $sq;
			$where .= $sq;
		} else {
			if ($is_client_table || strlen(trim($this->where))) {
				$sq = " WHERE ";
				if ($is_client_table) {
					if (!$this->is_pdo) {
						$sq .= "{$alias}.client_id='{$this->client_id}' ";
					} else {
						$sq .= "{$alias}.client_id=:client_id ";
						$this->whereParam[':client_id'] = $this->client_id;
					}
				}
				if (strlen(trim($this->where))) {
					if ($is_client_table) {
						if (strtoupper(substr(trim($this->where), 0, 4)) == 'AND ') $sq .= trim($this->where);
						else $sq .= "AND ".trim($this->where);
					} else {
						if (strtoupper(substr(trim($this->where), 0, 4)) == 'AND ') {
							$sq .= substr(trim($this->where), 4);
						} else {
							$sq .= trim($this->where);
						}
					}
				}
				$sql .= $sq;
				$where .= $sq;
			}
		}
		if (strlen(trim($this->order))) {
			$sq = " {$this->order}";
			$sql .= $sq;
			$where .= $sq;
		}
		if (strlen(trim($this->group))) {
			$sq = " {$this->group}";
			$sql .= $sq;
			$where .= $sq;
		}
		if ($this->pagesize>0) {
			$sq = " LIMIT {$this->pagesize}";
			$sql .= $sq;
			$where .= $sq;
		}
		$sql = str_replace($this->tbp_placeholder, $this->tbp, $sql);
		$sql = preg_replace_callback('/(__\w+__)/', function($match) {
			return $this->tbp.strtolower($match[1]);
		}, $sql);
		if ($this->log) SQL::write_log($sql);
		$this->sql = $sql;
		$this->sqls[] = $sql;
		$result = false;
		if (!$this->debug) {
			$id = 0;
			$prev = NULL;
			if (!in_array(str_replace($this->tbp, '', $this->table), $this->non_access_log_tables)) {
				if (defined('DIRNAME') && DIRNAME=='gm' && !$this->is_sqlite3 && strpos($this->table, "{$this->tbp}admin")===false) {
					$sq = "SELECT * FROM {$this->table} {$where}";
					$prev = $this->db->get_row($sq);
					$this->sql_count++;
					if ($prev) {
						$id = $prev->id;
						$prev = json_decode(json_encode($prev, JSON_UNESCAPED_UNICODE), true);
					}
				}
			}
			try {
				$this->beginTransaction();
				$result = $this->db->query($sql, array_merge($this->setParam, $this->whereParam));
				$this->commit();
			} catch (Exception $e) {
				$this->rollBack();
				$ex = new Exception;
				$trace = $sql."\n".$e->getMessage()."\n".$ex->getTraceAsString();
				defined('ERROR_FILE') && SQL::write_log($trace, ERROR_FILE);
				throw new RuntimeException("SQL Error: {$e}");
			}
			$this->getPDOError();
			if ($result) {
				if ($this->returnObj) {
					$this->sql_count++;
					if (intval($this->db->get_var("SELECT COUNT(*) FROM {$this->table} {$where}"))>1) {
						$result = $this->db->get_results("SELECT * FROM {$this->table} {$where}");
					} else {
						$result = $this->db->get_row("SELECT * FROM {$this->table} {$where}");
					}
					$this->sql_count++;
				}
			} else {
				if ($this->returnObj) $result = NULL;
			}
			if (!in_array(str_replace($this->tbp, '', $this->table), $this->non_access_log_tables)) {
				if (defined('DIRNAME') && DIRNAME=='gm' && !$this->is_sqlite3 && strpos($this->table, "{$this->tbp}admin")===false) {
					$sq = "SELECT * FROM {$this->table} WHERE id='{$id}'";
					$row = $this->db->get_row($sq);
					$this->sql_count++;
					if ($row && $prev) {
						$user_id = 0;
						$user_name = '';
						if (isset($_SESSION['admin']) && is_object($_SESSION['admin'])) {
							$user_id = $_SESSION['admin']->id;
							$user_name = $_SESSION['admin']->name;
						}
						$row = json_decode(json_encode($row, JSON_UNESCAPED_UNICODE), true);
						$prevrow = array_diff_assoc($prev, $row);
						$row = array_diff_assoc($row, $prev);
						if (count($prevrow) && count($row)) {
							$prevstr = str_replace('","', '", "', json_encode($prevrow, JSON_UNESCAPED_UNICODE));
							$rowstr = str_replace('","', '", "', json_encode($row, JSON_UNESCAPED_UNICODE));
							$table = $this->dbslave ? $this->dbslave->get_results("SHOW FULL COLUMNS FROM {$this->table}") : $this->db->get_results("SHOW FULL COLUMNS FROM {$this->table}");
							$this->sql_count++;
							foreach ($prevrow as $k => $g) {
								foreach ($table as $item) {
									if ($item->Field == $k) {
										if (strlen($item->Comment)) {
											$Comment = str_replace('，', ',', $item->Comment);
											$Comment = explode(',', $Comment);
											$prevstr = str_replace('"'.$k.'":', $Comment[0].':', $prevstr);
											$rowstr = str_replace('"'.$k.'":', $Comment[0].':', $rowstr);
										}
										break;
									}
								}
							}
							$t = $this->dbslave ? $this->dbslave->get_row("SHOW TABLE STATUS LIKE '{$this->table}'") : $this->db->get_row("SHOW TABLE STATUS LIKE '{$this->table}'");
							$this->sql_count++;
							$content = "{$user_name}<font color=\"orange\">更新</font>{$t->Comment} id [{$id}]<br />原数据 <font color=\"gray\">".addslashes($prevstr)."</font><br />改为 <font color=\"gray\">".addslashes($rowstr)."</font>";
							$client_field = (defined('IS_SAAS') && IS_SAAS) ? 'client_id, ' : '';
							$client_value = (defined('IS_SAAS') && IS_SAAS) ? "'{$this->client_id}', " : '';
							$sq = "INSERT INTO {$this->tbp}access_log ({$client_field}user_id, type, content, ip, add_time)
								VALUES ({$client_value}'{$user_id}', '1', '{$content}', '{$this->ip}', '".time()."')";
							$this->db->query($sq);
							$this->sql_count++;
						}
					}
				}
			}
		}
		if ($this->smarty && $this->smarty->caching) {
			$this->smarty->clearAllCache();
		}
		$this->sql_count++;
		return $result;
	}
	//删除记录
	public function delete($where='') {
		$tableLong = $alias = $this->table;
		if (stripos($alias, ' ')!==false) {
			$alias = preg_split('/\s+/', $alias);
			$tableLong = $alias[0];
			$alias = $alias[1];
		}
		$is_client_table = (defined('IS_SAAS') && IS_SAAS && !$this->is_sqlite3 && !$this->skipClient && !$this->is_operator_platform && !in_array(str_replace($this->tbp, '', $tableLong), $this->non_client_tables));
		if ( !(is_string($where) && !strlen($where)) && !(is_array($where) && !count($where)) ) $this->where($where);
		$where = '';
		$sql = "DELETE FROM {$this->table}";
		if (is_numeric($this->where)) {
			$sq = " WHERE ";
			if ($is_client_table) {
				if (!$this->is_pdo) {
					$sq .= "{$alias}.client_id='{$this->client_id}' AND ";
				} else {
					$sq .= "{$alias}.client_id=:client_id AND ";
					$this->whereParam[':client_id'] = $this->client_id;
				}
			}
			if (!$this->is_pdo) {
				$sq .= "{$alias}.id='{$this->where}'";
			} else {
				$sq .= "{$alias}.id=:id";
				$this->whereParam[':id'] = $this->where;
			}
			$sql .= $sq;
			$where .= $sq;
		} else {
			if ($is_client_table || strlen(trim($this->where))) {
				$sq = " WHERE ";
				if ($is_client_table) $sq .= "{$alias}.client_id='{$this->client_id}' ";
				if (strlen(trim($this->where))) {
					if ($is_client_table) {
						if (strtoupper(substr(trim($this->where), 0, 4)) == 'AND ') $sq .= trim($this->where);
						else $sq .= "AND ".trim($this->where);
					} else {
						if (strtoupper(substr(trim($this->where), 0, 4)) == 'AND ') {
							$sq .= substr(trim($this->where), 4);
						} else {
							$sq .= trim($this->where);
						}
					}
				}
				$sql .= $sq;
				$where .= $sq;
			}
		}
		if (strlen(trim($this->order))) {
			$sq = " {$this->order}";
			$sql .= $sq;
			$where .= $sq;
		}
		if (strlen(trim($this->group))) {
			$sq = " {$this->group}";
			$sql .= $sq;
			$where .= $sq;
		}
		if ($this->pagesize>0) {
			$sq = " LIMIT {$this->pagesize}";
			$sql .= $sq;
			$where .= $sq;
		}
		$sql = str_replace($this->tbp_placeholder, $this->tbp, $sql);
		$sql = preg_replace_callback('/(__\w+__)/', function($match) {
			return $this->tbp.strtolower($match[1]);
		}, $sql);
		if ($this->log) SQL::write_log($sql);
		$this->sql = $sql;
		$this->sqls[] = $sql;
		$res = NULL;
		$result = false;
		if (!$this->debug) {
			$is_table_split = in_array(str_replace($this->tbp, '', $tableLong), $this->split_tables);
			if ($is_table_split) {
				$ids = '';
				$res = $this->db->get_results("SELECT `id` FROM {$this->table} {$where}");
				$this->sql_count++;
				if (is_array($res) && count($res)) {
					$results = array();
					foreach ($res as $k=>$o) $results[] = $o->id;
					$ids = implode(',', $results);
				}
				if (strlen($ids)) {
					$this->db->query("DELETE FROM `{$tableLong}_id` WHERE `id` IN ({$ids})");
					$this->sql_count++;
				}
			}
			if ($this->returnObj) {
				$this->sql_count++;
				if (intval($this->db->get_var("SELECT COUNT(*) FROM {$this->table} {$where}"))>1) {
					$res = $this->db->get_results("SELECT * FROM {$this->table} {$where}");
				} else {
					$res = $this->db->get_row("SELECT * FROM {$this->table} {$where}");
				}
				$this->sql_count++;
			}
			if (!in_array(str_replace($this->tbp, '', $this->table), $this->non_access_log_tables)) {
				if (defined('DIRNAME') && DIRNAME=='gm' && !$this->is_sqlite3 && strpos($this->table, "{$this->tbp}admin")===false) {
					$sq = "SELECT * FROM {$this->table} {$where}";
					$row = $this->db->get_row($sq);
					$this->sql_count++;
					if ($row) {
						$rowstr = str_replace('","', '", "', json_encode($row, JSON_UNESCAPED_UNICODE));
						$table = $this->dbslave ? $this->dbslave->get_results("SHOW FULL COLUMNS FROM {$this->table}") : $this->db->get_results("SHOW FULL COLUMNS FROM {$this->table}");
						$this->sql_count++;
						$row = json_decode($rowstr, true);
						foreach ($row as $k => $g) {
							foreach ($table as $item) {
								if ($item->Field == $k) {
									if (strlen($item->Comment)) {
										$Comment = str_replace('，', ',', $item->Comment);
										$Comment = explode(',', $Comment);
										$rowstr = str_replace('"'.$k.'":', $Comment[0].':', $rowstr);
									}
									break;
								}
							}
						}
						$user_id = 0;
						$user_name = '';
						if (isset($_SESSION['admin']) && is_object($_SESSION['admin'])) {
							$user_id = $_SESSION['admin']->id;
							$user_name = $_SESSION['admin']->name;
						}
						$t = $this->dbslave ? $this->dbslave->get_row("SHOW TABLE STATUS LIKE '{$this->table}'") : $this->db->get_row("SHOW TABLE STATUS LIKE '{$this->table}'");
						$this->sql_count++;
						$content = "{$user_name}<font color=\"red\">删除</font>{$t->Comment}<br /><font color=\"gray\">".addslashes($rowstr).'</font>';
						$client_field = (defined('IS_SAAS') && IS_SAAS) ? 'client_id, ' : '';
						$client_value = (defined('IS_SAAS') && IS_SAAS) ? "'{$this->client_id}', " : '';
						$sq = "INSERT INTO {$this->tbp}access_log ({$client_field}user_id, type, content, ip, add_time)
							VALUES ({$client_value}'{$user_id}', '1', '{$content}', '{$this->ip}', '".time()."')";
						$this->db->query($sq);
						$this->sql_count++;
					}
				}
			}
			try {
				$this->beginTransaction();
				$result = $this->db->query($sql, $this->whereParam);
				$this->commit();
			} catch (Exception $e) {
				$this->rollBack();
				$ex = new Exception;
				$trace = $sql."\n".$e->getMessage()."\n".$ex->getTraceAsString();
				defined('ERROR_FILE') && SQL::write_log($trace, ERROR_FILE);
				throw new RuntimeException("SQL Error: {$e}");
			}
			$this->getPDOError();
		}
		if ($res) $result = $res;
		if ($this->smarty && $this->smarty->caching) {
			$this->smarty->clearAllCache();
		}
		$this->sql_count++;
		return $result;
	}
	//自执行SQL, params为boolean:true即不restore
	public function query($sql, $params=array()) {
		if (!is_bool($params) || !$params) $this->restore();
		$sql = str_replace($this->tbp_placeholder, $this->tbp, $sql);
		$sql = preg_replace_callback('/(__\w+__)/', function($match) {
			return $this->tbp.strtolower($match[1]);
		}, $sql);
		if ($this->log) SQL::write_log($sql);
		$this->sql = $sql;
		$this->sqls[] = $sql;
		$result = false;
		if (!$this->debug) {
			try {
				$whereParam = array();
				if ($this->is_pdo) {
					if (is_array($params)) {
						foreach ($params as $key=>$param) {
							$whereParam[":{$key}"] = $param;
						}
					}
				}
				$this->beginTransaction();
				if (preg_match("/^INSERT\s+/i", $sql)) {
					$this->db->query($sql, $whereParam);
					$result = isset($this->db->insert_id) ? intval($this->db->insert_id) : 0;
					if ($result==0) {
						preg_match('/^INSERT\s+INTO\s+([\w`]+)/i', $sql, $matcher);
						$table = trim($matcher[1], '`');
						$result = intval($this->db->get_var("SELECT MAX(id) FROM {$table}"));
						$this->sql_count++;
					}
				} else if (preg_match("/^SELECT\s+COUNT\(\*\)\s+/i", $sql)) {
					$result = intval($this->dbslave ? $this->dbslave->get_var($sql, $whereParam) : $this->db->get_var($sql, $whereParam));
				} else if (preg_match("/^(SELECT|SHOW|DESC|PRAGMA)\s+/i", $sql)) {
					$result = $this->dbslave ? $this->dbslave->get_results($sql, $whereParam) : $this->db->get_results($sql, $whereParam);
				} else if (preg_match("/^DESCRIBE\s+/i", $sql)) {
					$result = $this->dbslave ? $this->dbslave->get_row($sql, $whereParam) : $this->db->get_row($sql, $whereParam);
				} else {
					$result = $this->db->query($sql, $whereParam);
				}
				$this->commit();
			} catch (Exception $e) {
				$this->rollBack();
				$ex = new Exception;
				$trace = $sql."\n".$e->getMessage()."\n".$ex->getTraceAsString();
				defined('ERROR_FILE') && SQL::write_log($trace, ERROR_FILE);
				throw new RuntimeException("SQL Error: {$e}");
			}
			$this->getPDOError();
		}
		$this->sql_count++;
		return $result;
	}
	//获取/设置sql缓存
	private function _cacheSql($sql, $res=NULL) {
		$sq = $sql . (isset($_REQUEST['BRSR']) ? $_REQUEST['BRSR'] : '');
		$data = array('sql'=>$sql, 'add_time'=>time(), 'data'=>$res);
		if (!is_null($res) && $this->isezr && $this->ezr) {
			$data['navigation'] = $this->ezr->get_navigation();
			$data['navigations'] = $this->ezr->get_navigations();
		}
		if ($this->cached==-2) {
			$filename = md5($sq);
			$d = isset($_SESSION[$filename]) ? $filename : NULL;
			if ($d) $d = unserialize($d);
			if (is_null($res)) {
				if (!$d) return NULL;
				if ($this->isezr && $this->ezr) {
					if (isset($d['navigation'])) $this->ezr->set_navigation($d['navigation']);
					if (isset($d['navigations'])) $this->ezr->set_navigations($d['navigations']);
				}
				return $d['data'];
			}
			$_SESSION[$filename] = serialize($data);
		} else if (isset(self::$redis[$this->key])) {
			$d = self::$redis[$this->key]->get($sq);
			if ($d) $d = unserialize($d);
			if ($this->cached<0 || (is_array($d) && (time()-$d['add_time'])<$this->cached)) {
				if (is_null($res)) {
					if ($this->isezr && $this->ezr) {
						if (isset($d['navigation'])) $this->ezr->set_navigation($d['navigation']);
						if (isset($d['navigations'])) $this->ezr->set_navigations($d['navigations']);
					}
					return $d['data'];
				}
			}
			if (!is_null($res)) {
				self::$redis[$this->key]->set($sq, serialize($data));
				if ($this->cached>0) self::$redis[$this->key]->expireAt($sq, time()+$this->cached);
			}
		} else if (isset(self::$memcached[$this->key])) {
			$d = self::$memcached[$this->key]->get($sq);
			if ($this->cached<0 || (is_array($d) && (time()-$d['add_time'])<$this->cached)) {
				if (is_null($res)) {
					if ($this->isezr && $this->ezr) {
						if (isset($d['navigation'])) $this->ezr->set_navigation($d['navigation']);
						if (isset($d['navigations'])) $this->ezr->set_navigations($d['navigations']);
					}
					return $d['data'];
				}
			}
			if (!is_null($res)) {
				self::$memcached[$this->key]->set($sq, $data, MEMCACHE_COMPRESSED, $this->cached>0 ? time()+$this->cached : 0);
			}
		} else {
			$path = (defined('IS_SAAS') && IS_SAAS) ? ($this->client_id>0 ? "/{$this->client_id}" : '') : '';
			$dir = ROOT_PATH . (defined('CACHE_SQL_PATH') ? CACHE_SQL_PATH : '/temp') . $path;
			SQL::makedir($dir);
			$filename = $dir . '/' . md5($sq);
			if (file_exists($filename)) {
				$d = file_get_contents($filename);
				if ($d) $d = unserialize($d);
				if ($this->cached<0 || (is_array($d) && (time()-$d['add_time'])<$this->cached)) {
					if (is_null($res)) {
						if ($this->isezr && $this->ezr) {
							if (isset($d['navigation'])) $this->ezr->set_navigation($d['navigation']);
							if (isset($d['navigations'])) $this->ezr->set_navigations($d['navigations']);
						}
						return $d['data'];
					}
				}
			}
			if (!is_null($res)) file_put_contents($filename, serialize($data));
		}
		return $res;
	}
	//清除SQL缓存
	public function clearCached($sql='') {
		$this->removeCached($sql);
	}
	public function removeCached($sql='') {
		$sq = $sql . (isset($_REQUEST['BRSR']) ? $_REQUEST['BRSR'] : '');
		if (isset(self::$redis[$this->key])) {
			if (strlen($sql)) {
				self::$redis[$this->key]->del($sq);
			} else {
				self::$redis[$this->key]->flushall();
			}
		} else if (isset(self::$memcached[$this->key])) {
			if (strlen($sql)) {
				self::$memcached[$this->key]->delete($sq);
			} else {
				self::$memcached[$this->key]->flush();
			}
		} else {
			$path = (defined('IS_SAAS') && IS_SAAS) ? ($this->client_id>0 ? "/{$this->client_id}" : '') : '';
			$dir = ROOT_PATH . (defined('CACHE_SQL_PATH') ? CACHE_SQL_PATH : '/temp') . $path;
			SQL::makedir($dir);
			if (strlen($sql)) {
				$filename = $dir . '/' . md5($sq);
				unlink($filename);
			} else {
				if ($handle = opendir($dir)) {
					while ($filename = readdir($handle)) {
						$path = $dir . '/' . $filename;
						if (is_dir($path) || $filename == '.' || $filename == '..') continue;
						unlink($path);
					}
					closedir($handle);
				}
			}
		}
	}
	//是否存在表
	public function tableExist($table) {
		//ALTER TABLE table ENGINE=InnoDB //修改数据表引擎为InnoDB
		$table = preg_replace('/^'.$this->tbp.'/', '', $table);
		$has_table = false;
		if ($this->is_sqlite3) {
			$sql = "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='{$this->tbp}{$table}'";
		} else {
			$sql = "SHOW TABLES LIKE '{$this->tbp}{$table}'";
		}
		if ($this->cached!=0) {
			$result = $this->_cacheSql($sql);
		} else {
			$result = $this->query($sql);
		}
		if ($result) $has_table = true;
		return $has_table;
	}
	//创建数据表,可创建sqlite3,例SQL::share('~db.sqlite')->tableCreate
	/*SQL::share()->tableCreate(array(
		'table' => array(
			'table_engine' => 'InnoDB',
			'table_auto_increment' => 10,
			'table_comment' => '表注释',
			'id' => array('type'=>'key'),
			'name' => array('type'=>'varchar(255)', 'comment'=>'名称', 'charset'=>'utf8mb4'),
			'price' => array('type'=>'decimal(10,2)', 'default'=>'0.00', 'comment'=>'价格'),
			'content' => array('type'=>'text', 'comment'=>'内容'),
			'clicks' => array('type'=>'int', 'comment'=>'点击数', 'index'=>'clicks'),
			'add_time' => array('type'=>'int', 'comment'=>'添加时间')
		)
	));*/
	public function tableCreate($tables=array(), $re_create=false) {
		$sql = '';
		if (!is_array($tables)) $sql = $tables;
		else {
			foreach ($tables as $table_name=>$table_info) {
				$table_name = str_replace($this->tbp_placeholder, $this->tbp, $table_name);
				$table_name = preg_replace_callback('/(__\w+__)/', function($match) {
					return $this->tbp.strtolower($match[1]);
				}, $table_name);
				if (!$re_create && $this->tableExist($table_name)) continue;
				$key_field = '';
				$field_sql = '';
				$index = array(); //索引
				$this->tableRemove($table_name);
				$sql .= "CREATE TABLE `{$table_name}` (".PHP_EOL;
				foreach ($table_info as $field_name=>$field_info) {
					if (in_array($field_name, array('table_engine', 'table_auto_increment', 'table_comment'))) continue;
					$field_sql .= "`{$field_name}`";
					if (isset($field_info['type'])) {
						if ($field_info['type'] == 'key') {
							$key_field = $field_name;
							$field_sql .= $this->is_sqlite3 ? " integer NOT NULL PRIMARY KEY AUTOINCREMENT" : " int(11) NOT NULL AUTO_INCREMENT";
						}
						else if ($this->is_sqlite3 && stripos($field_info['type'], 'varchar')!==false) {
							$field_sql .= " text";
						}
						else if ($this->is_sqlite3 && stripos($field_info['type'], 'int')!==false) {
							$field_sql .= " integer";
						}
						else if ($this->is_sqlite3 && stripos($field_info['type'], 'decimal')!==false) {
							$field_sql .= " numeric";
						}
						else $field_sql .= " ".$field_info['type'];
					} else {
						$field_sql .= $this->is_sqlite3 ? " text" : " varchar(255)";
					}
					if (!$this->is_sqlite3 && isset($field_info['charset'])) $field_sql .= " CHARACTER SET ".$field_info['charset'];
					if (isset($field_info['default'])) {
						$field_sql .= " DEFAULT '".$field_info['default']."'";
					} else if (isset($field_info['type']) && (stripos($field_info['type'], 'int')!==false || stripos($field_info['type'], 'decimal')!==false)) {
						$field_sql .= stripos($field_info['type'], 'decimal')!==false ? " DEFAULT '0.00'" : " DEFAULT '0'";
					} else if (isset($field_info['type']) && stripos($field_info['type'], 'varchar')!==false) {
						$field_sql .= " DEFAULT NULL";
					}
					if (!$this->is_sqlite3 && isset($field_info['index'])) $index[] = array('name'=>$field_info['index'], 'column'=>$field_name);
					if (!$this->is_sqlite3 && isset($field_info['comment'])) $field_sql .= " COMMENT '".str_replace("'", "\'", $field_info['comment'])."'";
					$field_sql .= ','.PHP_EOL;
				}
				if (!$this->is_sqlite3 && strlen($key_field)) $field_sql .= "PRIMARY KEY (`{$key_field}`)";
				$field_sql = trim(trim($field_sql), ',');
				if (count($index)) {
					foreach ($index as $i) {
						$field_sql .= ','.PHP_EOL.'KEY `'.$i['name'].'` (`'.$i['column'].'`)';
					}
				}
				$sql .= trim(trim($field_sql), ',').PHP_EOL;
				$sql .= ")";
				if (!$this->is_sqlite3) {
					$engine = isset($table_info['table_engine']) ? $table_info['table_engine'] : 'InnoDB';
					$sql .= " ENGINE={$engine}";
					if (isset($table_info['table_auto_increment'])) $sql .= " AUTO_INCREMENT=".$table_info['table_auto_increment'];
					$sql .= " DEFAULT CHARSET=utf8";
					if (isset($table_info['table_comment'])) $sql .= " COMMENT='".str_replace("'", "\'", $table_info['table_comment'])."'";
				}
				$sql .= ";";
			}
		}
		if (strlen($sql)) return $this->query($sql);
		return $this;
	}
	//删除表
	public function tableRemove($table) {
		return $this->query("DROP TABLE IF EXISTS `{$table}`");
	}
	//临时设置单表插入数据量阈值(例如导入Excel数据), 不设会报 Got a packet bigger than 'max_allowed_packet' bytes, PHP生命周期结束后会自动恢复
	public function max_allowed_packet($max_allowed_packet=1048576000) {
		$this->db->query("SET GLOBAL max_allowed_packet={$max_allowed_packet}");
		return $this;
	}
	//事务处理
	//捕获PDO错误信息
	private function getPDOError() {
		if (!$this->is_pdo) return;
		if ($this->db->db->errorCode()!='00000') {
			$arrayError = $this->db->db->errorInfo();
			$this->rollBack();
			defined('ERROR_FILE') && SQL::write_log($arrayError[2], ERROR_FILE);
			throw new RuntimeException('PDO Error: '.$arrayError[2]);
		}
	}
	//闭包形式调用事务
	public function transaction($callback) {
		if (!$callback || is_string($callback) || !is_callable($callback)) return $this;
		try {
			$this->beginTransaction();
			$callback($this);
			$this->commit();
		} catch (Exception $e) {
			$this->rollBack();
			$ex = new Exception;
			$trace = $e->getMessage()."\n".$ex->getTraceAsString();
			defined('ERROR_FILE') && SQL::write_log($trace, ERROR_FILE);
			throw new RuntimeException("SQL Error: {$e}");
		}
		$this->getPDOError();
		return $this;
	}
	//是否执行事务中
	public function inTransaction() {
		if (!$this->is_pdo) return false;
		return $this->db->db->inTransaction();
	}
	//开启事务
	public function beginTransaction() {
		if ($this->is_pdo) {
			if (!$this->inTransaction()) {
				$this->db->db->setAttribute(PDO::ATTR_AUTOCOMMIT, false); //关闭自动提交
				$this->db->db->beginTransaction();
			}
		} else if ($this->dbtype=='INNODB') {
			$this->db->query('SET AUTOCOMMIT=0');
			$this->db->query('BEGIN');
		}
		return $this;
	}
	//提交事务
	public function commit() {
		if ($this->is_pdo) {
			if ($this->inTransaction()) $this->db->db->commit();
			$this->db->db->setAttribute(PDO::ATTR_AUTOCOMMIT, true); //提交完成后开启自动提交
		} else if ($this->dbtype=='INNODB') {
			$this->db->query('COMMIT');
			$this->db->query('SET AUTOCOMMIT=1');
		}
		return $this;
	}
	//回滚事务
	public function rollBack() {
		if ($this->is_pdo) {
			if ($this->inTransaction()) $this->db->db->rollBack();
			$this->db->db->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
		} else if ($this->dbtype=='INNODB') {
			$this->db->query('ROLLBACK');
			$this->db->query('SET AUTOCOMMIT=1');
		}
		return $this;
	}
	//字段类型设定
	private function _columnsType($res, $columns_type) {
		if ($res && (is_array($res) || is_object($res))) {
			$is_array = is_array($res);
			$res = json_decode(json_encode($res, JSON_UNESCAPED_UNICODE), true);
			foreach ($columns_type as $k => $t) {
				if ($is_array) {
					foreach ($res as $g) {
						foreach ($t as $value) {
							if (isset($g[$value])) $g[$value] = $k($g[$value]);
						}
					}
				} else {
					foreach ($t as $value) {
						if (isset($res[$value])) $res[$value] = $k($res[$value]);
					}
				}
			}
			$res = json_decode(json_encode($res, JSON_UNESCAPED_UNICODE), $is_array);
		}
		return $res;
	}
	//转义特殊字符, \x00 \n \r \ ' " \x1a
	private function _escape($value) {
		$value = stripslashes($value);
		if (!is_numeric($value)) {
			if ($this->is_sqlite3) {
				$value = "'" . SQLite3::escapeString($value) . "'";
			} else if ($this->db instanceof ezSQL_mysql) {
				$value = "'" . mysql_real_escape_string($value) . "'";
			} else {
				$value = "'" . mysqli_real_escape_string($this->db->dbh, $value) . "'";
			}
		}
		return $value;
	}
	//恢复参数
	public function restore() {
		//$this->is_sqlite3 = false;
		$this->left = array();
		$this->right = array();
		$this->inner = array();
		$this->cross = array();
		$this->union = array();
		$this->unionFields = array();
		$this->where = '';
		$this->distincter = '';
		$this->order = '';
		$this->group = '';
		$this->having = '';
		$this->isezr = false;
		$this->firstpage = '';
		$this->lastpage = '';
		$this->prevpage = '';
		$this->nextpage = '';
		$this->isJumppage = true;
		$this->rewritePage = '';
		$this->offset = 0;
		$this->pagesize = 0;
		$this->cached = 0;
		$this->returnObj = false;
		$this->returnArray = false;
		$this->glue = '';
		$this->skipClient = false;
		$this->href = '';
		//$this->debug = false;
		$this->whereParam = array();
		$this->setParam = array();
		$this->log = false;
		$this->page = '';
		$this->pagelink = '';
		$this->wherebase64 = '';
		$this->sql = '';
		//$this->sqls = array();
		//$this->sql_count = 0;
		return $this;
	}
	//循环创建目录,对应根目录
	public static function makedir($destination, $create_html=false) {
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
	//写log文件
	public static function write_log($content='', $file='/temp/sql.txt', $trace=false, $echo=false) {
		$filename = ROOT_PATH.str_replace(ROOT_PATH, '', $file);
		$traceStr = '';
		if ($trace) {
			$e = new Exception;
			$trace = $e->getTraceAsString();
			$traceStr = "\n\n".$trace;
		}
		if (is_array($content) || is_object($content)) $content = json_encode($content, JSON_UNESCAPED_UNICODE);
		file_put_contents($filename, date('Y-m-d H:i:s')."==============================\n".$content.$traceStr."\n@==============================\n\n", FILE_APPEND);
		if ($echo) echo $content.'<br />';
	}
	//输出所有信息
	public function info() {
		print '<pre>';
		@print_r($this);
		print '</pre>';
		exit;
	}
}

//子项表达式
class SQL_RAW {
	public $expression;
	public function __construct($expression) {
		$this->expression = $expression;
	}
}
