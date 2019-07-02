<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\jpush;

use cmf\lib\Plugin;
require_once dirname(__DIR__) . '/jpush/sdk/autoload.php';
use JPush\Client as JPush;
use JMessage\JMessage as JMessage;
use JMessage\IM\Message;
use JMessage\IM\User;
use JMessage\IM\Resource;

class JpushPlugin extends Plugin {
  
    public $info = [
        'name'        => 'Jpush',
        'title'       => '极光推送',
        'description' => '极光推送',
        'status'      => 1,
        'author'      => 'Lixue',
        'version'     => '1.0'
    ];
    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install() {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall() {
        return true;//卸载成功返回true，失败false
    }

    /**
     * 消息推送
     * @param $param
     * @return bool
     */
    public function pushMsg($param) {
        $config        = $this->getConfig();
        $logFile = CMF_ROOT . 'data/runtime/jpush.log';
        $client = new JPush($config['app_key'], $config['master_secret'], $logFile);
        $pusher = $client->push();
        //设置发送平台
        $pusher->setPlatform(array('ios', 'android'));
        if (!isset($param['user_id'])) {//未指定发送对象，发送给所有人
            $pusher->addAllAudience();
        } else {//指定发送对象：发送给单独用户别名
            $userIds = explode(',',$param['user_id']);
            foreach ($userIds as $k => $v) {
                $userIds[$k] = $v."PUSH";
            }
            $pusher->addAlias($userIds);
        }

        $extras = ['type' => $param['type']];
        $title = $config['title'];
        if (isset($param['user_id'])) {
            $extras['uid'] = $param['user_id'];
        }
        if (isset($param['anchor_id'])) {
            $extras['uid'] = $param['anchor_id'];
        }
        if (isset($param['room'])) {
            $extras['room'] = $param['room'];
        }
        if (isset($param['url'])) {
            $extras['url'] = $param['url'];
        }
        if (isset($param['avatar'])) {
            $extras['avatar'] = $param['avatar'];
        }
        if (isset($param['nickname'])) {
            $extras['nickname'] = $param['nickname'];
        }
        if (isset($param['title'])) {
            $title = $param['title'];
        }
        //设置发送内容
        $pusher->setNotificationAlert($param['msg'])
            ->iosNotification($param['msg'], array(
                'sound' => 'default',
                'badge' => '+1',
//                'content-available' => true,
//                'mutable-content' => true,
//                'category' => 'jiguang',
                'extras' => $extras,
            ))
            ->androidNotification($param['msg'], array(
                'title' => $title,
                // 'builder_id' => 2,
                'extras' => $extras,
            ));
        try {
            $pusher->send();
        } catch (\JPush\Exceptions\JPushException $e) {
          var_dump($e);
            return false;
        }
        return true;
    }
  
    /**
     * 注册IM用户
     * @param $param
     */
    public function userJpushReg($param) {
        $config        = $this->getConfig();
        $jm = new JMessage($config['app_key'], $config['master_secret']);
        $user = new User($jm);
        return $user->register($param['name'],$param['pass']);
    }

    /**
     * 更新IM用户信息
     * @param $param
     * @return mixed
     */
    public function userJpushEdit($param) {
        $config        = $this->getConfig();
        $jm = new JMessage($config['app_key'], $config['master_secret']);
        $user = new User($jm);
        return $user->update($param['name'],$param['data']);
        //return $user->show($param['name']);
    }
  
    /**
     * 删除IM用户
     * @param $param
     */
    public function userJpushDel($username) {
        $config        = $this->getConfig();
        $jm = new JMessage($config['app_key'], $config['master_secret']);
        $user = new User($jm);
        return $user->delete($username);
    }

    /**
     * 发送IM消息
     * @param $param
     * @return mixed
     */
    public function sendJpushMsg($param) {
        $config        = $this->getConfig();
        $jm = new JMessage($config['app_key'], $config['master_secret']);
        $message = new Message($jm);

        $from = [
            'id'   => $param['from_id'],
            'type' => 'user'
        ];

        $target = [
            'id'   => $param['target_id'],
            'type' => 'single'
        ];
      
        if ($param['msg_type'] == 'image') {
            $result = $message->sendImage(1, $from, $target, $param['msg_body'],['notifiable'=>false]);
        } elseif ($param['msg_type'] == 'voice') {
            $result = $message->sendVoice(1, $from, $target, $param['msg_body'],['notifiable'=>false]);
        } elseif ($param['msg_type'] == 'custom') {
            $msg = ['text' => $param['text']];
            $result = $message->sendCustom(1, $from, $target, $msg,['notifiable'=>false]);
        } else {
            $msg = ['text' => $param['text']];
            $result = $message->sendText(1, $from, $target, $msg,['notifiable'=>false]);
        }

        return $result;
    }

    /**
     * 上传IM资源文件
     * @param $param
     * @return mixed
     */
    public function uploadJpush($param)
    {
        $config        = $this->getConfig();
        $jm = new JMessage($config['app_key'], $config['master_secret']);
        $resource = new Resource($jm);

        return $resource->upload($param['type'], $param['url']);
    }
}