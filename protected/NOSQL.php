<?php
//Developed by @mario 20201029
/*
NoSql操作类
*/
class NOSQL {
	private static $instance = NULL;
	private static $operator = NULL;
	private $dbs = array(); //数据库组
	private $db = 0; //当前数据库
	private $table = ''; //表名
	private $joinTable = array(); //表联接
	private $where = array(); //条件数组
	private $sort = array(); //排序数组
	private $offset = 0; //记录偏移量
	private $pagesize = 0; //返回记录数
	private $returnObj = false; //插入/更新/删除返回操作的记录
	private $returnArray = false; //查询结果转为数组形式
	private $glue = ''; //returnArray为true时把数组合并为字符串的分隔符,空为不合并

	public function __construct($config=array()) {
		if (!class_exists('Redis') && !class_exists('Memcached')) $this->error("Uncaught Error: Class 'Redis' and 'Memcached' are not found", 1);
		if (!function_exists('redisd') && !function_exists('mcached')) $this->error("Uncaught Error: Function 'redisd' and 'mcached' are not found", 1);
		$host = isset($config['host']) ? $config['host'] : '127.0.0.1';
		$port = isset($config['port']) ? $config['port'] : (class_exists('Redis') ? 6379 : 11211);
		self::$operator = class_exists('Redis') ? redisd($host, $port) : mcached($host, $port);
		//任何为false的串,存在redis中都是空串,只有在key不存在时,才会返回false
	}
	//创建单例
	public static function share($table='', $db=NULL) {
		if (!self::$instance) self::$instance = new self();
		if (strlen($table)) self::$instance->from($table, $db);
		return self::$instance;
	}
	//操作者
	public function operator() {
		return self::$operator;
		//echo get_class(self::$operator);
	}
	//改变数据库
	public function database($db) {
		if (!class_exists('Redis')) return $this; //Memcached不支持数据库切换
		if (is_string($db)) {
			$dbKey = 'NOSQL_DB_ARRAY';
			$dbs = self::$operator->get($dbKey);
			if ($dbs) $this->dbs = unserialize($dbs);
			$index = array_search($db, $this->dbs);
			if ($index !== false) {
				$db = $index;
			} else {
				$this->dbs[] = $db;
				self::$operator->set($dbKey, serialize($this->dbs));
				$db = count($this->dbs) - 1;
			}
		}
		if (is_numeric($db) && $db<0) $this->error("Database '{$db}' is not exist", 1);
		$this->db = $db;
		self::$operator->select($db);
		return $this;
	}
	//指定表名
	public function from($table, $db=NULL) {
		$this->clear();
		if (!is_null($db)) $this->database($db);
		$this->table = $table;
		return $this;
	}
	//左联接
	public function left($table, $on='') {
		$alias = $table;
		if (strpos($alias, ' ')!==false) {
			$alias = preg_split('/\s+/', $alias);
			$table = $alias[0];
			$alias = $alias[1];
		}
		$rs = self::$operator->get($table);
		if (!$rs) $this->error("Left join table '{$table}' is not exist");
		$this->joinTable[] = array('table'=>$table, 'alias'=>$alias, 'on'=>$on, 'type'=>'left');
		return $this;
	}
	//右联接
	public function right($table, $on='') {
		$alias = $table;
		if (strpos($alias, ' ')!==false) {
			$alias = preg_split('/\s+/', $alias);
			$table = $alias[0];
			$alias = $alias[1];
		}
		$rs = self::$operator->get($table);
		if (!$rs) $this->error("Right join table '{$table}' is not exist");
		$this->joinTable[] = array('table'=>$table, 'alias'=>$alias, 'on'=>$on, 'type'=>'right');
		return $this;
	}
	//等值联接
	public function inner($table, $on='') {
		$alias = $table;
		if (strpos($alias, ' ')!==false) {
			$alias = preg_split('/\s+/', $alias);
			$table = $alias[0];
			$alias = $alias[1];
		}
		$rs = self::$operator->get($table);
		if (!$rs) $this->error("Inner join table '{$table}' is not exist");
		$this->joinTable[] = array('table'=>$table, 'alias'=>$alias, 'on'=>$on, 'type'=>'inner');
		return $this;
	}
	//查询条件, ->where(['id'=>1])->where(['name'=>'keyword', 'title'=>"%keyword%", 'id'=>'>1', 'id'=>'<=2', 'id'=>'!=3', 'id'=>'in (1,2)', 'id'=>'not in (1,2)', 'name'=>'is null', 'name'=>'is not null'])
	//可指定OR值, 如 ->where(['or' => [['name'=>'keyword1'], ['name'=>'keyword2']]])
	public function where($where) {
		if (is_array($where)) {
			foreach ($where as $k=>$v) {
				$this->where[trim($k)] = $v;
			}
		} else if (is_numeric($where)) {
			$this->where['id'] = $where; //默认是id
		} else {
			$this->error("where param must be Array or Number");
		}
		return $this;
	}
	//排序, ->sort([ 'id'=>[SORT_ASC|1], 'sort'=>[SORT_DESC|-1] ])
	public function sort($sort) {
		if (is_array($sort)) {
			$this->sort = array();
			foreach ($sort as $k=>$v) {
				$k = trim($k);
				$this->sort[$k] = $v;
			}
		} else {
			$this->error("sort param must be Array");
		}
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
		$this->offset($offset);
		$this->pagesize($pagesize);
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
	//记录数量
	public function count() {
		return intval($this->value());
	}
	//记录是否存在
	public function exist() {
		return $this->count() > 0;
	}
	//查询字段值总和
	public function sum($field, $type='intval') {
		$array = $this->returnArray()->find($field);
		if (!count($array)) return 0;
		return $type(array_sum($array));
	}
	//查询字段值平均值
	public function avg($field, $type='intval') {
		$array = $this->returnArray()->find($field);
		if (!count($array)) return 0;
		$avg = array_sum($array) / count($array);
		return $type($avg);
	}
	//查询字段值最小值
	public function min($field, $type='intval') {
		$rs = $this->sort(array("{$field}"=>SORT_ASC))->find();
		if (!$rs) return 0;
		return $type($rs[0]->{$field});
	}
	//查询字段值最大值
	public function max($field, $type='intval') {
		$rs = $this->sort(array("{$field}"=>SORT_DESC))->find();
		if (!$rs) return 0;
		return $type($rs[0]->{$field});
	}
	//查询字段值
	public function value($field='', $type='', $callback=NULL) {
		if (!strlen($field)) {
			$rs = $this->find('*', $callback);
			if (!$rs) return 0;
			return count($rs);
		}
		$val = $this->pagesize(-1)->find($field, $callback);
		if ($field == 'id') { //默认id为整数型
			$val = intval($val);
		} else if (strlen($type)) {
			$val = $type($val);
		}
		return $val;
	}
	//查询某列的值
	public function column($field, $callback=NULL) {
		return $this->returnArray()->find($field, $callback);
	}
	//查询单条记录
	public function row($field='*', $callback=NULL) {
		return $this->pagesize(1)->find($field, $callback);
	}
	//查询, callback为查询结果先执行一次callback,接收一个参数:查询结果
	public function find($fields='*', $callback=NULL) {
		if (is_string($fields) && !strlen($fields)) $fields = '*';
		$table = $alias = $this->table;
		if (strpos($alias, ' ')!==false) {
			$alias = preg_split('/\s+/', $alias);
			$table = $alias[0];
			$alias = $alias[1];
		}
		$master = self::$operator->get($table);
		if (!$master) $this->error("Table '{$table}' is not exist");
		$master = unserialize($master);
		if (is_string($fields)) $fields = preg_split('/,(?![^(]*\))/', $fields);
		$filterWhere = function($where, $g, $a) use (&$filterWhere, &$alias, &$master) {
			if (!is_array($where)) return array(false);
			$isMatches = array();
			if (count($where)) {
				foreach ($where as $k=>$v) {
					$column = trim($k);
					if (strtoupper($column)=='OR') {
						if (!is_array($v)) {
							$isMatches[] = false;
							break;
						}
						if (count($v)) {
							$_isMatches = array();
							foreach ($v as $_v) {
								$__isMatches = $filterWhere($_v, $g, $a);
								if (!count($__isMatches)) $__isMatches[] = true;
								foreach ($__isMatches as $__match) {
									$_isMatches[] = $__match;
								}
							}
							$isMatch = false;
							if (!count($_isMatches)) $_isMatches[] = true;
							foreach ($_isMatches as $match) {
								if ($match) {
									$isMatch = true;
									break;
								}
							}
							if ( !$isMatch ) {
								$isMatches[] = false;
								break;
							}
						}
					} else {
						preg_match('/^(\w+\.)?(\w+)$/', $column, $matcher);
						if ( $matcher && count($matcher) && !strlen($matcher[1]) && $a!=$alias && property_exists($master[0], $matcher[2]) && property_exists($g, $matcher[2]) ) {
							$this->error("Column '".$column."' in on clause is ambiguous");
						}
						if ( !$matcher || !count($matcher) || (!strlen($matcher[1]) && $a!=$alias) || (strlen($matcher[1]) && "{$a}."!=$matcher[1]) ) continue;
						$k = $matcher[2];
						if (!property_exists($g, $k)) $this->error("Unknown column {$column}");
						$comparePattern = '/^\s*([!<>=]{1,2})([\s\d.]+)\s*$/';
						$inPattern = '/^\s*(not\s+)?in\s+\(([^)]+)\)\s*$/i';
						$nullPattern = '/^\s*is(\s+not)\s+null\s*$/i';
						$likePattern = '/^\s*like\s+(%?)(.+?)(%?)\s*$/i';
						if (preg_match($comparePattern, $v)) {
							preg_match($comparePattern, $v, $compareMatcher);
							$compare = $compareMatcher[1];
							$number = floatval(str_replace(' ', '', $compareMatcher[2]));
							if ( $compare=='<' && floatval($g->{$k})>=$number ) {
								$isMatches[] = false;
								break;
							}
							if ( $compare=='>' && floatval($g->{$k})<=$number ) {
								$isMatches[] = false;
								break;
							}
							if ( $compare=='<=' && floatval($g->{$k})>$number ) {
								$isMatches[] = false;
								break;
							}
							if ( $compare=='>=' && floatval($g->{$k})<$number ) {
								$isMatches[] = false;
								break;
							}
							if ( ($compare=='<>' || $compare=='!=') && floatval($g->{$k})==$number ) {
								$isMatches[] = false;
								break;
							}
						} else if (preg_match($inPattern, $v)) {
							preg_match($inPattern, $v, $inMatcher);
							$not = $inMatcher[1];
							$_marks = explode(',', $inMatcher[2]);
							$marks = array();
							array_walk($_marks, function($value) use (&$marks){
								$marks[] = trim(trim($value, "'"), '"');
							});
							if ( !count($marks) ) {
								$isMatches[] = false;
								break;
							}
							if ( !strlen($not) && !in_array($g->{$k}, $marks) ) {
								$isMatches[] = false;
								break;
							}
							if ( strlen($not) && in_array($g->{$k}, $marks) ) {
								$isMatches[] = false;
								break;
							}
						} else if (preg_match($nullPattern, $v)) {
							preg_match($nullPattern, $v, $nullMatcher);
							$not = $nullMatcher[1];
							if ( !strlen($not) && !is_null($g->{$k}) ) {
								$isMatches[] = false;
								break;
							}
							if ( strlen($not) && is_null($g->{$k}) ) {
								$isMatches[] = false;
								break;
							}
						} else if (preg_match($likePattern, $v)) {
							preg_match($likePattern, $v, $likeMatcher);
							if ( !strlen($likeMatcher[1]) && !preg_match('/^'.str_replace('/', '\/', $likeMatcher[2]).'/i', $g->{$k}) ) {
								$isMatches[] = false;
								break;
							}
							if ( !strlen($likeMatcher[3]) && !preg_match('/'.str_replace('/', '\/', $likeMatcher[2]).'$/i', $g->{$k}) ) {
								$isMatches[] = false;
								break;
							}
							if ( strlen($likeMatcher[1]) && strlen($likeMatcher[3]) && !preg_match('/'.str_replace('/', '\/', $likeMatcher[2]).'/i', $g->{$k}) ) {
								$isMatches[] = false;
								break;
							}
						} else if ($g->{$k}!=$v) {
							$isMatches[] = false;
							break;
						}
					}
				}
			}
			return $isMatches;
		};
		if (count($this->joinTable)) {
			$onPattern = '/^(\w+\.)?(\w+)$/';
			foreach ($this->joinTable as $g) {
				$join = self::$operator->get($g['table']);
				$join = unserialize($join);
				$joinAlias = $g['alias'];
				$joinType = $g['type'];
				if ($joinType=='left') {
					$slaver = $join;
				} else if ($joinType=='right') {
					$slaver = $master;
					$master = $join;
					$joinAlias = $alias;
					$alias = $g['alias'];
				} else {
					$slaver = $join;
				}
				$on = explode('=', $g['on']);
				preg_match($onPattern, trim($on[0]), $firstMatcher);
				preg_match($onPattern, trim($on[1]), $secondMatcher);
				if ( !$firstMatcher || !count($firstMatcher) || (!isset($master[0]->{$firstMatcher[2]}) && !$slaver) || (!isset($master[0]->{$firstMatcher[2]}) && $slaver && !isset($slaver[0]->{$firstMatcher[2]})) ) {
					$this->error("Unknown column '".trim($on[0])."' in 'on clause'");
				}
				if ( !$secondMatcher || !count($secondMatcher) || (!isset($master[0]->{$secondMatcher[2]}) && !$slaver) || (!isset($master[0]->{$secondMatcher[2]}) && $slaver && !isset($slaver[0]->{$secondMatcher[2]})) ) {
					$this->error("Unknown column '".trim($on[1])."' in 'on clause'");
				}
				if ( !strlen($firstMatcher[1]) && isset($master[0]->{$firstMatcher[2]}) && $slaver && isset($slaver[0]->{$firstMatcher[2]}) ) {
					$this->error("Column '".$firstMatcher[2]."' in on clause is ambiguous");
				}
				if ( !strlen($secondMatcher[1]) && isset($master[0]->{$secondMatcher[2]}) && $slaver && isset($slaver[0]->{$secondMatcher[2]}) ) {
					$this->error("Column '".$secondMatcher[2]."' in on clause is ambiguous");
				}
				$array = array();
				foreach ($master as $m) {
					$nonMatcher = true;
					if ($slaver) {
						foreach ($slaver as $s) {
							if (!strlen($firstMatcher[1])) {
								if (property_exists($m, $firstMatcher[2])) {
									if ($m->{$firstMatcher[2]}==$s->{$secondMatcher[2]}) {
										$nonMatcher = false;
										$isMatches = $filterWhere($this->where, $s, $joinAlias);
										$isMatch = false;
										if (!count($isMatches)) $isMatches[] = true;
										foreach ($isMatches as $match) {
											if ($match) {
												$isMatch = true;
												break;
											}
										}
										if (!$isMatch) continue;
										foreach ($fields as $f) {
											$f = trim($f);
											if ( (strpos($f, '.')===false && !property_exists($s, $f)) || strpos($f, $firstMatcher[1])!==false ) continue;
											$f = str_replace($secondMatcher[1], '', $f);
											$s = json_decode(json_encode($s, JSON_UNESCAPED_UNICODE), true);
											if ($f=='*') {
												foreach ($s as $k=>$v) {
													$m->{$k} = $v;
												}
											} else {
												$a = $f;
												if (stripos($f, ' as ')!==false) {
													$a = preg_split('/\s+as\s+/i', $f);
													$f = trim($a[0]);
													$a = trim($a[1]);
												}
												$m->{$a} = $s[$f];
											}
										}
										$array[] = $m;
									}
								} else {
									if ($m->{$secondMatcher[2]}==$s->{$firstMatcher[2]}) {
										$nonMatcher = false;
										$isMatches = $filterWhere($this->where, $s, $joinAlias);
										$isMatch = false;
										if (!count($isMatches)) $isMatches[] = true;
										foreach ($isMatches as $match) {
											if ($match) {
												$isMatch = true;
												break;
											}
										}
										if (!$isMatch) continue;
										foreach ($fields as $f) {
											$f = trim($f);
											if ( (strpos($f, '.')===false && !property_exists($s, $f)) || strpos($f, $secondMatcher[1])!==false ) continue;
											$f = str_replace($firstMatcher[1], '', $f);
											$s = json_decode(json_encode($s, JSON_UNESCAPED_UNICODE), true);
											if ($f=='*') {
												foreach ($s as $k=>$v) {
													$m->{$k} = $v;
												}
											} else {
												$a = $f;
												if (stripos($f, ' as ')!==false) {
													$a = preg_split('/\s+as\s+/i', $f);
													$f = trim($a[0]);
													$a = trim($a[1]);
												}
												$m->{$a} = $s[$f];
											}
										}
										$array[] = $m;
									}
								}
							} else {
								if ("{$alias}."==$firstMatcher[1]) {
									if ($m->{$firstMatcher[2]}==$s->{$secondMatcher[2]}) {
										$nonMatcher = false;
										$isMatches = $filterWhere($this->where, $s, $joinAlias);
										$isMatch = false;
										if (!count($isMatches)) $isMatches[] = true;
										foreach ($isMatches as $match) {
											if ($match) {
												$isMatch = true;
												break;
											}
										}
										if (!$isMatch) continue;
										foreach ($fields as $f) {
											$f = trim($f);
											if ( (strpos($f, '.')===false && !property_exists($s, $f)) || strpos($f, $firstMatcher[1])!==false ) continue;
											$f = str_replace($secondMatcher[1], '', $f);
											$s = json_decode(json_encode($s, JSON_UNESCAPED_UNICODE), true);
											if ($f=='*') {
												foreach ($s as $k=>$v) {
													$m->{$k} = $v;
												}
											} else {
												$a = $f;
												if (stripos($f, ' as ')!==false) {
													$a = preg_split('/\s+as\s+/i', $f);
													$f = trim($a[0]);
													$a = trim($a[1]);
												}
												$m->{$a} = $s[$f];
											}
										}
										$array[] = $m;
									}
								} else {
									if ($m->{$secondMatcher[2]}==$s->{$firstMatcher[2]}) {
										$nonMatcher = false;
										$isMatches = $filterWhere($this->where, $s, $joinAlias);
										$isMatch = false;
										if (!count($isMatches)) $isMatches[] = true;
										foreach ($isMatches as $match) {
											if ($match) {
												$isMatch = true;
												break;
											}
										}
										if (!$isMatch) continue;
										foreach ($fields as $f) {
											$f = trim($f);
											if ( (strpos($f, '.')===false && !property_exists($s, $f)) || strpos($f, $secondMatcher[1])!==false ) continue;
											$f = str_replace($firstMatcher[1], '', $f);
											$s = json_decode(json_encode($s, JSON_UNESCAPED_UNICODE), true);
											if ($f=='*') {
												foreach ($s as $k=>$v) {
													$m->{$k} = $v;
												}
											} else {
												$a = $f;
												if (stripos($f, ' as ')!==false) {
													$a = preg_split('/\s+as\s+/i', $f);
													$f = trim($a[0]);
													$a = trim($a[1]);
												}
												$m->{$a} = $s[$f];
											}
										}
										$array[] = $m;
									}
								}
							}
						}
					}
					if ($joinType!='inner' && $nonMatcher)  {
						foreach ($fields as $f) {
							$f = trim($f);
							if ( (strpos($f, '.')===false && property_exists($m, $f)) || preg_match('/^'.$alias.'\./', $f) ) continue;
							$f = str_replace("{$joinAlias}.", '', $f);
							$a = $f;
							if (stripos($f, ' as ')!==false) {
								$a = preg_split('/\s+as\s+/i', $f);
								$a = trim($a[1]);
							}
							$m->{$a} = NULL;
						}
						$array[] = $m;
					}
				}
				$master = $array;
			}
		}
		$obj = NULL;
		$array = array();
		if (count($master) && count($this->sort)) {
			$fieldArr = array();
			foreach ($master as $k=>$v) {
				foreach ($this->sort as $name=>$sort) {
					$fieldArr[$name][$k] = $v->{$name};
				}
			}
			$code = 'array_multisort(';
			foreach ($this->sort as $name=>$sort) {
				$code .= '$fieldArr["'.$name.'"], '.(($sort==SORT_ASC||$sort==1)?'SORT_ASC':'SORT_DESC').', ';
			}
			$code .= '$rs);';
			eval($code);
		}
		$j = 0;
		for ($i=$this->offset; $i<count($master); $i++) {
			$g = $master[$i];
			$row = new stdClass();
			foreach ($fields as $_k=>$f) {
				$f = preg_replace('/^'.$alias.'\./', '', trim($f));
				if ($f=='*') {
					$values = array();
					$d = json_decode(json_encode($g, JSON_UNESCAPED_UNICODE), true);
					foreach ($d as $k=>$v) {
						$values[$k] = $v;
					}
					$row = json_decode(json_encode($values, JSON_UNESCAPED_UNICODE));
				} else {
					$isSlaver = false;
					if (strpos($f, '.')!==false) {
						$isSlaver = true;
						$f = explode('.', $f);
						$f = $f[1];
					}
					$a = $f;
					if (stripos($f, ' as ')!==false) {
						$a = preg_split('/\s+as\s+/i', $f);
						$f = $isSlaver ? trim($a[1]) : trim($a[0]);
						$a = trim($a[1]);
					}
					$quotePattern = '/^([\'"])([^\1]*?)\1$/';
					$numberPattern = '/^([\d.]+)$/';
					$isnullPattern = '/^IFNULL\(\s*(\w+)\s*,\s*([\'"])([^\2]*)\2\s*\)$/i';
					$concatPattern = '/^CONCAT\(\s*(([\'"])[^\2]*?\2|\w+)([^)]*)\)$/i';
					$md5Pattern = '/^MD5\(\s*([^)]+)\s*\)$/i';
					$leftPattern = '/^LEFT\(\s*(([\'"])[^\2]*?\2|\w+)\s*,\s*(\d+)\s*\)$/i';
					$rightPattern = '/^RIGHT\(\s*(([\'"])[^\2]*?\2|\w+)\s*,\s*(\d+)\s*\)$/i';
					$trimPattern = '/^TRIM\(\s*([^)]+)\s*\)$/i';
					$replacePattern = '/^REPLACE\(\s*(\w+)([^)]+)\)$/i';
					$substringPattern = '/^SUBSTRING\(\s*(([\'"])[^\2]*?\2|\w+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i';
					$midPattern = '/^MID\(\s*(([\'"])[^\2]*?\2|\w+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i';
					$lcasePattern = '/^LCASE\(\s*([^)]+)\s*\)$/i';
					$ucasePattern = '/^UCASE\(\s*([^)]+)\s*\)$/i';
					$reversePattern = '/^REVERSE\(\s*([^)]+)\s*\)$/i';
					$value = property_exists($g, $f) ? $g->{$f} : '';
					$analyzeField = function($v) use (&$analyzeField, $g, $quotePattern, $numberPattern, $isnullPattern, $concatPattern, $md5Pattern, $leftPattern, $rightPattern, $trimPattern, $replacePattern, $substringPattern, $midPattern, $lcasePattern, $ucasePattern, $reversePattern) {
						if (preg_match($quotePattern, $v)) {
							preg_match($quotePattern, $v, $matcher);
							$v = $matcher[2];
						} else if (preg_match($numberPattern, $v)) {
							preg_match($numberPattern, $v, $matcher);
							$v = $matcher[1] * 1;
						} else if (preg_match($isnullPattern, $v)) {
							preg_match($isnullPattern, $v, $matcher);
							if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
							$v = is_null($g->{$matcher[1]}) ? $matcher[3] : $g->{$matcher[1]};
						} else if (preg_match($concatPattern, $v)) {
							preg_match($concatPattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]);
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = $g->{$matcher[1]};
							}
							if (strlen(trim($matcher[3]))) {
								$strArr = preg_split('/,(?![^(]*\))/', trim(trim(trim($matcher[3]), ',')));
								foreach ($strArr as $str) {
									$str = trim($str);
									if (preg_match('/^[\'"]/', $str)) {
										$v .= preg_replace('/(^[\'"]|[\'"]$)/', '', $str);
									} else {
										if (!property_exists($g, $str)) $this->error("Unknown column ".$str);
										$v .= $g->{$str};
									}
								}
							}
						} else if (preg_match($md5Pattern, $v)) {
							preg_match($md5Pattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = md5(preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]));
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = md5($g->{$matcher[1]});
							}
						} else if (preg_match($leftPattern, $v)) {
							preg_match($leftPattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = substr(preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]), 0, intval($matcher[3]));
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = substr($g->{$matcher[1]}, 0, intval($matcher[3]));
							}
						} else if (preg_match($rightPattern, $v)) {
							preg_match($rightPattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = substr(preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]), strlen($matcher[1])-intval($matcher[3]));
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = substr($g->{$matcher[1]}, strlen($g->{$matcher[1]})-intval($matcher[3]));
							}
						} else if (preg_match($trimPattern, $v)) {
							preg_match($trimPattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = trim(preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]));
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = trim($g->{$matcher[1]});
							}
						} else if (preg_match($replacePattern, $v)) {
							preg_match($replacePattern, $v, $matcher);
							if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
							$strArr = explode(',', trim(trim(trim($matcher[2]), ',')));
							$v = str_replace(preg_replace('/(^[\'"]|[\'"]$)/', '', trim($strArr[0])), preg_replace('/(^[\'"]|[\'"]$)/', '', trim($strArr[1])), $g->{$matcher[1]});
						} else if (preg_match($substringPattern, $v)) {
							preg_match($substringPattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = substr(preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]), intval($matcher[3]), intval($matcher[4]));
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = substr($g->{$matcher[1]}, intval($matcher[3]), intval($matcher[4]));
							}
						} else if (preg_match($midPattern, $v)) {
							preg_match($midPattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = substr(preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]), intval($matcher[3]), intval($matcher[4]));
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = substr($g->{$matcher[1]}, intval($matcher[3]), intval($matcher[4]));
							}
						} else if (preg_match($lcasePattern, $v)) {
							preg_match($lcasePattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = strtolower(preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]));
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = strtolower($g->{$matcher[1]});
							}
						} else if (preg_match($ucasePattern, $v)) {
							preg_match($ucasePattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = strtoupper(preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]));
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = strtoupper($g->{$matcher[1]});
							}
						} else if (preg_match($reversePattern, $v)) {
							preg_match($reversePattern, $v, $matcher);
							if (preg_match('/^[\'"]/', $matcher[1])) {
								$v = preg_replace('/(^[\'"]|[\'"]$)/', '', $matcher[1]);
							} else {
								if (!property_exists($g, $matcher[1])) $this->error("Unknown column ".$matcher[1]);
								$v = $g->{$matcher[1]};
							}
							$str2arr = function($str) {
								$length = mb_strlen($str);
								$array = array();
								for ($i=0; $i<$length; $i++) {
									$array[] = mb_substr($str, $i, 1, 'utf-8');
								}
								return $array;
							};
							$v = $str2arr($v);
							krsort($v);
							$v = implode('', $v);
						}
						if (preg_match($quotePattern, $v) || preg_match($numberPattern, $v) || preg_match($isnullPattern, $v) || preg_match($concatPattern, $v) || preg_match($md5Pattern, $v) ||
							preg_match($leftPattern, $v) || preg_match($rightPattern, $v) || preg_match($trimPattern, $v) || preg_match($replacePattern, $v) || preg_match($substringPattern, $v) ||
							preg_match($midPattern, $v) || preg_match($lcasePattern, $v) || preg_match($ucasePattern, $v) || preg_match($reversePattern, $v)) {
							$v = $analyzeField($v);
						}
						return $v;
					};
					if (preg_match($quotePattern, $f) || preg_match($numberPattern, $f) || preg_match($isnullPattern, $f) || preg_match($concatPattern, $f) || preg_match($md5Pattern, $f) ||
						preg_match($leftPattern, $f) || preg_match($rightPattern, $f) || preg_match($trimPattern, $f) || preg_match($replacePattern, $f) || preg_match($substringPattern, $f) ||
						preg_match($midPattern, $f) || preg_match($lcasePattern, $f) || preg_match($ucasePattern, $f) || preg_match($reversePattern, $f)) {
						$value = $analyzeField($f);
					} else if (!property_exists($g, $f)) {
						$this->error("Unknown column {$f}");
					}
					$row->{$a} = $value;
				}
			}
			$g = $row;
			$isMatches = $filterWhere($this->where, $g, $alias);
			$isMatch = false;
			if (!count($isMatches)) $isMatches[] = true;
			foreach ($isMatches as $match) {
				if ($match) {
					$isMatch = true;
					break;
				}
			}
			if (!$isMatch) continue;
			$array[] = $g;
			$j++;
			if ($this->pagesize<0 || $this->pagesize==1 || ($this->pagesize>0 && $j==$this->pagesize-1)) break;
		}
		if ($this->pagesize<0) {
			$obj = is_array($fields) ? $array[0]->{$fields[0]} : '';
		} else if ($this->pagesize==0) {
			$obj = count($array) ? $array : NULL;
			if ($this->returnArray) {
				if (is_array($obj) && count($obj)) {
					$keys = get_object_vars($obj[0]);
					if (count(array_keys($keys))==1) {
						$results = array();
						$obj = json_decode(json_encode($obj, JSON_UNESCAPED_UNICODE), true);
						foreach ($obj as $k=>$o) {
							$keys = array_keys($o);
							$results[] = $o[$keys[0]];
						}
						$obj = $results;
					} else {
						$obj = json_decode(json_encode($obj, JSON_UNESCAPED_UNICODE), true);
					}
				} else {
					$obj = array();
				}
				if (strlen($this->glue)) $obj = implode($this->glue, $obj);
			}
		} else if ($this->pagesize==1) {
			$obj = $array[0];
			if ($this->returnArray) {
				if ($obj) {
					$obj = json_decode(json_encode($obj, JSON_UNESCAPED_UNICODE), true);
				} else {
					$obj = array();
				}
				if (strlen($this->glue)) $obj = implode($this->glue, $obj);
			}
		} else {
			$obj = count($array) ? $array : NULL;
			if ($this->returnArray) {
				if (is_array($obj) && count($obj)) {
					if (is_array($fields) && count($fields)==1) {
						$results = array();
						$obj = json_decode(json_encode($obj, JSON_UNESCAPED_UNICODE), true);
						foreach ($obj as $k=>$o) {
							$keys = array_keys($o);
							$results[] = $o[$keys[0]];
						}
						$obj = $results;
					} else {
						$obj = json_decode(json_encode($obj, JSON_UNESCAPED_UNICODE), true);
					}
				} else {
					$obj = array();
				}
				if (strlen($this->glue)) $obj = implode($this->glue, $obj);
			}
		}
		if ($obj && $callback && !is_string($callback) && is_callable($callback)) $obj = $callback($obj);
		return $obj;
	}
	//插入记录
	public function insert($data) {
		$array = self::$operator->get($this->table);
		$array = $array ? unserialize($array) : array();
		$_data = array();
		if (array_values($data) === $data) { //数字索引, 如 [{name:'name1', price:0.01}, {name:'name2', price:0.01}]
			$_data = $data;
		} else { //键名索引, 如 [name:['name1', 'name2'], price:['0.01', '0.01']]
			foreach ($data as $k => $d) {
				if (is_array($d)) { //多条插入
					for ($i=0; $i<count($d); $i++) {
						$_data[$i][$k] = $d[$i];
					}
				} else { //单条插入
					$_data[0][$k] = $d;
				}
			}
		}
		$id = self::$operator->get("{$this->table}_AUTO_INCREMENT");
		foreach ($_data as $d) {
			$id = $id ? intval($id)+1 : 1;
			if (!is_array($d)) $d = json_decode(json_encode($d, JSON_UNESCAPED_UNICODE), true);
			$arr = array();
			foreach ($d as $k=>$g) {
				$arr[trim($k)] = $g;
			}
			$d = json_decode(json_encode($arr, JSON_UNESCAPED_UNICODE));
			$d->id = $id;
			$array[] = $d;
		}
		self::$operator->set($this->table, serialize($array));
		self::$operator->set("{$this->table}_AUTO_INCREMENT", $id);
		if ($this->returnObj) return $data;
		return $id;
	}
	//更新记录
	public function update($data) {
		$result = false;
		$rs = $this->find();
		if ($rs) {
			$result = true;
			if (function_exists('array_column')) {
				$ids = array_column($rs, 'id');
			} else {
				$ids = array();
				array_walk($rs, function($value) use (&$ids){
					$ids[] = $value['id'];
				});
			}
			$list = array();
			$matches = array();
			$array = self::$operator->get($this->table);
			$array = $array ? unserialize($array) : array();
			foreach ($array as $arr) {
				if (!is_array($arr)) $arr = json_decode(json_encode($arr, JSON_UNESCAPED_UNICODE), true);
				if (in_array($arr['id'], $ids)) {
					foreach ($data as $k=>$g) {
						$k = trim($k);
						if (!isset($arr[$k])) $this->error("Unknown column {$k}");
						if (is_array($g)) { //处理类似 logins=logins+1 等, 如 'logins'=>['+1']
							if (count($g)==1) {
								$v = implode('', $g);
								$g = preg_match('/^[-+]?\d+$/', $v) ? $arr[$k] + (str_replace('+', '', $v) * 1) : $arr[$k] . $v;
							} else {
								$g = $arr[$k] . implode('', $g);
							}
						}
						$arr[$k] = $g;
					}
					$matches[] = json_decode(json_encode($arr, JSON_UNESCAPED_UNICODE));
				}
				$list[] = json_decode(json_encode($arr, JSON_UNESCAPED_UNICODE));
			}
			self::$operator->set($this->table, serialize($list));
			if ($this->returnObj) {
				$result = count($matches)>1 ? $matches : $matches[0];
			}
		}
		return $result;
	}
	//删除记录
	public function delete($where=array()) {
		$result = false;
		if (is_array($where)) $this->where($where);
		$rs = $this->find();
		if ($rs) {
			$result = true;
			if ($this->returnObj) {
				if (count($rs)>1) {
					$obj = $rs;
				} else {
					$obj = $rs[0];
				}
				$result = $obj;
			}
			if (function_exists('array_column')) {
				$ids = array_column($rs, 'id');
			} else {
				$ids = array();
				array_walk($rs, function($value) use (&$ids){
					$ids[] = $value['id'];
				});
			}
			$list = array();
			$array = self::$operator->get($this->table);
			$array = $array ? unserialize($array) : array();
			foreach ($array as $arr) {
				if (!is_array($arr)) $arr = json_decode(json_encode($arr, JSON_UNESCAPED_UNICODE), true);
				if (!in_array($arr['id'], $ids)) $list[] = json_decode(json_encode($arr, JSON_UNESCAPED_UNICODE));
			}
			self::$operator->set($this->table, serialize($list));
		}
		return $result;
	}
	//是否存在表
	public function tableExist($table) {
		$array = self::$operator->get($table);
		return $array ? true : false;
	}
	//删除表
	public function tableRemove($table) {
		if (class_exists('Redis')) {
			self::$operator->del($table);
			self::$operator->del("{$table}_AUTO_INCREMENT");
			//self::$operator->flushall();
		} else {
			self::$operator->delete($table);
			self::$operator->delete("{$table}_AUTO_INCREMENT");
			//self::$operator->flush();
		}
		return $this;
	}
	//清空参数
	public function clear() {
		$this->db = 0;
		$this->table = '';
		$this->joinTable = array();
		$this->where = array();
		$this->sort = array();
		$this->offset = 0;
		$this->pagesize = 0;
		$this->returnObj = false;
		$this->returnArray = false;
		$this->glue = '';
		return $this;
	}
	//显示错误
	private function error($msg, $level=0) {
		$backtrace = debug_backtrace();
		$bt = $backtrace[$level+1];
		$backtrace = $backtrace[$level];
		die("<b>Fatal Error:</b> {$msg} in <b>".$bt['file']."</b> on line <b>".$bt['line']."</b> at function line <b>".$backtrace['line']."</b>");
	}
}

/*
$rs = SQL::share('miniprogram')->pagesize(5)->find();
$data = array();
foreach ($rs as $g) $data[] = $g;
NOSQL::share()->tableRemove('miniprogram')->from('miniprogram')->insert($data);
$rs = NOSQL::share('miniprogram')->find();
echo json_encode($rs, JSON_UNESCAPED_UNICODE);exit;
*/
