<?php
return [
	//主数据库
	'db_master' => [
		//数据库服务器
		'host' => '127.0.0.1',
		//数据库账号
		'user' => 'root',
		//数据库密码
		'password' => '',
		//数据库编码
		'encoding' => 'utf8mb4',
		//数据库名称
		'name' => 'scene',
		//表名前缀
		'prefix' => 'sc_',
		//数据库类型, 有效值 MYSQL, PDO, SQLITE
		'type' => 'MYSQL'
	],
	//辅助数据库(从数据库)(只读)https://www.cnblogs.com/lelehellow/p/9633315.html
	'db_slave' => []
];
