<?php

return [
    // 默认磁盘
    'default' => env('filesystem.driver', 'local'),
    // 磁盘列表
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => app()->getRuntimePath() . 'storage',
        ],
        'public' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/uploads',
            // 磁盘路径对应的外部URL路径
            'url'        => '/uploads',
            // 可见性
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
	    'qiniu' => [ //完全可以自定义的名称
		    'type' => 'qiniu', //可以自定义,实际上是类名小写
		    'accessKey' => '1jQ9EudgAfVWML3gn9CjA5-0dh4Jmk1b14olgZgw', //七牛云的配置,accessKey
		    'secretKey' => 's2Y1x5alN0iTMbkIQICmxFO_fbusYpk1iQRXl1oa', //七牛云的配置,secretKey
		    'bucket' => 'images', //七牛云的配置,bucket空间名
		    'domain' => 'https://image.laokema.com' //七牛云的配置,domain
        ],
	    'ypyun' => [
		    'type' => 'ypyun',
		    'bucketname' => 'bangfang',
		    'operator_name' => 'bangfang2',
		    'operator_pwd' => 'WfZ9jXRJH#Ts',
		    'domain' => 'https://images.laokema.com'
        ]
    ],
];
