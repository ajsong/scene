<?php
//路由配置
return [
	/*
	'gm.cn' => [ //注意ckeditor配置的上传文件接口路径
		APPLICATION_PATH.'/gm/',
		'^api(\/(\w+)?)?(\/(\w+)?)?\/?(\?(.*))?$' => 'gm/api.php?app=$2&act=$4&$6',
		'^api\/(\w+)\/(\w+)(\/.+)$' => 'gm/api.php?app=$1&act=$2&_param=$3',
		'^(\w+)?(\/(\w+)?)?\/?(\?(.*))?$' => 'gm/index.php?app=$1&act=$3&$5',
		'^(\w+)\/(\w+)(\/.+)$' => 'gm/api.php?app=$1&act=$2&_param=$3'
	],
	*/
	'^url$' => 'api/index.php?app=home&act=url',
	'^about(\?(.*))?$' => 'api/index.php?tpl=about&$2',
	'^(api)\/scene\/(\d+)$' => 'api/$1.php?app=scene&act=edit&id=$2',
	'^(index)\/scene\/(\d+)$' => 'api/index.php?app=scene&act=edit&id=$2',
	'^article\/(\d+)\/(\d+)(\?.*)?$' => 'api/index.php?app=article&act=detail&admin_id=$1&id=$2',
	'^v\/(\w{11})(\?.*)?$' => 'api/index.php?app=wap&act=detail&code=$1',
	'^c\/(\w{11})(\?.*)?$' => 'api/api.php?app=wap&act=detail&code=$1',
	'^(wap|api|index)(\/v([\d.]+))?(\/(\w+)?)?(\/(\w+)?)?\/?(\?(.*))?$' => 'api/$1.php?ver=$3&app=$5&act=$7&$9',
	'^(wap|api|index)(\/v([\d.]+))?\/(\w+)\/(\w+)(\/.+)$' => 'api/$1.php?ver=$3&app=$4&act=$5&_param=$6'
];
