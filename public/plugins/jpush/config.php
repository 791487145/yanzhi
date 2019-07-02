<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
return [
    'app_key'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'AppKey', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'AppKey不能为空'
        ],
        'tip'     => '<a href="https://www.jiguang.cn" target="_blank">马上获取</a>' //表单的帮助提示
    ],
    'master_secret'                 => [
        'title'   => 'MasterSecret',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'MasterSecret不能为空'
        ],
        'tip'     => '<a href="https://www.jiguang.cn" target="_blank">马上获取</a>'
    ],
    'title'                 => [
        'title'   => '默认消息标题',
        'type'    => 'text',
        'value'   => '',
    ],
    'im_url'                 => [
        'title'   => 'IM接口',
        'type'    => 'text',
        'value'   => 'https://api.im.jpush.cn',
        'tip'     => '即时通讯接口URL'
    ]
];
					