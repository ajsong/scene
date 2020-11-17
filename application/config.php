<?php
$database = require_once(APPLICATION_PATH . '/database.php');
return array_merge([
	//授权码
	'platform_auth' => '22DC89Hx4IWdjbLBafShNxLnYHlVaY5c',
	//调试模式, false隐藏所有提示且记录到log, true显示所有错误
	'api_debug' => true,
	//表前缀占位符, 会被替换为表前缀字符串, 或者表字符串使用__XXX__, 将替换为 表前缀_xxx
	'db_tbp_placeholder' => '%tbp',
	//不加client_id条件的表
	'db_non_client_tables' => [],
	//不记录操作日志的表
	'db_not_access_log_tables' => ['access_log', 'article_attr', 'article_comment', 'blessing_attr', 'buddha_attr', 'buddha_comment'],
	//水平分表的表名
	'db_split_tables' => ['member_scene', 'member_scene_page', 'wechat_user', 'wechat_customer_mp', 'article_attr', 'article_comment', 'blessing_attr', 'buddha_attr', 'buddha_comment', 'video_attr', 'buddhaaudio_attr'],
	//前台检测登录状态APP、ACT (H5、WEB、小程序、全局默认)
	'not_check_login' => [
		'wap' => [],
		'web' => [],
		'mini' => [
			'home' => ['index'],
			'passport' => ['*'],
			'article' => ['*'],
			'blessing' => ['*'],
			'buddha' => ['*'],
			'buddhaaudio' => ['*'],
			'video' => ['*'],
			'qians' => ['*'],
			'other' => ['*'],
			'cron' => ['*']
		],
		'global' => [
			'home' => ['index', 'login', 'logout', 'url', 'wxtag', 'addWechat', 'deleteWechat', 'mario', 'article3', 'report'],
			'wap' => ['*'],
			'video' => ['*'],
			'article' => ['*'],
			'blessing' => ['*'],
			'buddha' => ['*'],
			'buddhaaudio' => ['*'],
			//'domains' => ['*'],
			//'jump' => ['*'],
			'qians' => ['*'],
			'tools' => ['*'],
			'passport' => ['*'],
			'other' => ['*'],
			'cron' => ['*']
		]
	],
	//可外站AJAX跨域的APP、ACT
	'access_allow' => [
		'wap' => ['detail', 'click'],
		'article' => ['item', 'click'],
		'video' => ['detail']
	],
	//上传的图片使用服务器存储, 0否(第三方存储), 1是
	'upload_local' => 0,
	//本地上传文件路径
	'upload_path' => '/public/uploads',
	//扩展库路径
	'extend_path' => '/public/extend',
	//REDIS参数
	'redis_setting' => [
		'host' => '127.0.0.1',
		'port' => 6379
	],
	//MEMCACHED参数
	'memcached_setting' => [
		'host' => '127.0.0.1',
		'port' => 11211
	],
	//SMARTY缓存
	'smarty_caching' => false,
	//SMARTY缓存时长, 单位秒
	'smarty_cache_lifetime' => 60 * 60,
	//SMARTY缓存路径
	'smarty_cache_path' => '/temp/cache_c',
	//SMARTY模板缓存路径
	'smarty_template_cache_path' => '/temp/templates_c',
	//SQL查询缓存路径
	'cache_sql_path' => '/temp/sql_c',
	//致命错误记录文件名
	'error_file' => '/temp/error.txt',
	//CRON定时器可执行IP白名单
	'cron_allow_ip' => ['127.0.0.1', '::1'],
	//加解密KEY
	'crypt_key' => 'MARIO_@AES_@20200404',
	//时区
	'default_timezone' => 'PRC',
	//是否SAAS
	'is_saas' => false,
	//腾讯云储存
	'cos_options' => [],
	//阿里云储存
	'oss_options' => [],
	//静态文件域名
	'oss_domain' => '<#oss_domain#>',
	//AJAX提交点击域名
	'click_domain' => '<#click_domain#>'
], $database);