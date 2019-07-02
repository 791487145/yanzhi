<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
return array (
	'app_id' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '直播APPID', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '登录腾讯云后台-接入管理-直播码接入' //表单的帮助提示
	),
	'biz_id' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '直播bizid', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '登录腾讯云后台-接入管理-直播码接入' //表单的帮助提示
	),
	'live_key' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '推流防盗链Key', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '登录腾讯云后台-接入管理-直播码接入-接入配置' //表单的帮助提示
	),
	'api_key' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => 'API鉴权Key', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '登录腾讯云后台-接入管理-直播码接入-接入配置' //表单的帮助提示
	),
	'huang_id' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '鉴黄防盗链ID', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '登录腾讯云后台-截图鉴黄' //表单的帮助提示
	),
	'huang_key' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '鉴黄防盗链Key', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '',// 表单的默认值
		'tip' => '登录腾讯云后台-截图鉴黄' //表单的帮助提示
	),
	'time' => array (// 在后台插件配置表单中的键名 ,会是config[text]
		'title' => '过期时间', // 表单的label标题
		'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
		'value' => '24',// 表单的默认值
		'tip' => '直播流过期时间，单位：小时' //表单的帮助提示
	),
);
					