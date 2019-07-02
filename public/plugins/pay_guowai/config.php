<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
return array (
	'pay_url' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '接口地址', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '第三方提供的接口地址' //表单的帮助提示
	),
    'mch_id' => array (// 在后台插件配置表单中的键名 ,会是config[text]
        'title' => '商户号', // 表单的label标题
        'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => '',// 表单的默认值
        'tip' => '第三方提供的商户号' //表单的帮助提示
    ),
	'key' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => 'KEY', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '商户密钥' //表单的帮助提示
	),
	'package_name' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => 'SDK包名', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '微信SDK支付的包名，由第三方提供' //表单的帮助提示
	),
	'name' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '商品名称', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '平台发送给第三方的商品名称' //表单的帮助提示
	),
	'redirect' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '支付回调', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '平台发送给第三方的支付回调URL' //表单的帮助提示
	),
	'notify' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '异步通知', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '平台发送给第三方的异步通知URL' //表单的帮助提示
	),
	'success' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '成功跳转', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '充值成功后跳转的URL' //表单的帮助提示
	),
);
					