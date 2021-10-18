<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::group('other', function() {
	Route::view('wxtool', 'wxtool');
	Route::get('phpinfo', function() {
		exit(phpinfo());
	});
	Route::get('ua', function() {
		exit($_SERVER['HTTP_USER_AGENT']);
	});
	Route::get('platform', function() {
		exit('<script>document.write(navigator.platform)</script>');
	});
});

Route::get('index/about', 'index/about');
Route::get('about', 'index/about');

Route::get('v/:code', 'wap/detail');
Route::get('c/:code', 'wap/detail')->append(['output'=>'json']);
/*->allowCrossDomain([
	'Access-Control-Allow-Origin' => '*', //允许所有地址跨域请求
	'Access-Control-Allow-Methods' => '*', //设置允许的请求方法, *表示所有, POST,GET,OPTIONS,DELETE
	'Access-Control-Allow-Credentials' => 'true', //设置允许请求携带cookie, 此时origin不能用*
	'Access-Control-Allow-Headers' => 'x-requested-with,content-type'
])*/

Route::group('scene', function() {
	Route::get(':id', 'scene/edit')->pattern(['id'=>'\d+']);
	Route::get('video', function() {
		return success('ok', 'scene/video.html');
	});
	Route::get('map', function() {
		return success('ok', 'scene/map.html');
	});
	Route::get('chart', function() {
		return success('ok', 'scene/chart.html');
	});
});
