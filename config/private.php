<?php
// +----------------------------------------------------------------------
// | 自定义设置
// +----------------------------------------------------------------------
return [
	//前台检测登录状态APP、ACT (H5、WEB、小程序、全局默认)
	'not_check_login' => [
		'wap' => [],
		'web' => [],
		'mini' => [
			'home' => ['index'],
			'passport' => ['*'],
			'article' => ['*'],
			'other' => ['*'],
			'cron' => ['*']
		],
		'global' => [
			'home' => ['index', 'login', 'logout', 'url', 'wxtag', 'addWechat', 'deleteWechat', 'mario'],
			'wap' => ['*'],
			'article' => ['*'],
			'domains' => ['*'],
			'passport' => ['*'],
			'other' => ['*'],
			'cron' => ['*']
		]
	],
	//可外站AJAX跨域的APP、ACT
	'access_allow' => [],
	//上传的图片使用第三方存储, 0或空字符使用本地存储, 1(默认七牛)或[qiniu|ypyun]第三方
	'upload_third' => 0,
	//本地上传文件路径
	'upload_path' => '/uploads',
	//CRON定时器可执行IP白名单
	'cron_allow_ip' => ['127.0.0.1', '::1', '106.53.232.241'],
	//加解密KEY
	'crypt_key' => 'MARIO_@AES_@20200401',
];
