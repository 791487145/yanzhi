<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------

namespace api\portal\controller;

use cmf\controller\RestBaseController;
use think\Db;

class PushController extends RestBaseController
{
    /**
     * 极光推送 - 推送测试
     */
    public function index()
    {
        $data1 = [
            'msg'=>"系统消息1111111",
            'type'      => 1
        ];
        $data2 = [
            'msg'=>"你好，聊天吗？",
            'title' => '张三',
            'type'      => 2,
            'user_id'   => 1309
        ];
        $data3 = [
            'msg'=>"张三请求与您视频通话",
            'type'      => 3,
            'user_id'   => 1309,
            'room'      => "1309_2"
        ];
        $data4 = [
            'msg'=>"推荐在线主播",
            'type'      => 4,
            'user_id'   => 1309,
            'url'       => "http://****"//视频URL
        ];
        $data5 = [
            'msg'=>"主播张三正在直播",
            'type'      => 9
        ];
        $push = hook_one("push_msg", $data3);
        $this->success('请求成功!', $push);
    }
    /**
     * 极光推送 - 推荐主播
     */
    public function anchorRecom()
    {
        $anchorData = $this->getRecommend();
        if (!$anchorData['ret'])
        {
            $this->error("no_anchor");
        }
        $anchorData = $anchorData['data'];
        //对登录2分钟内的用户进行主播推荐
        $time = time() - 120;
        $user = Db::name('user')
            ->field('group_concat(id) ids')
            ->where(['last_login_time'=> ['>=',$time]])
            ->where('user_type',2)
            ->where('is_zombie',0)
            ->where('is_virtual',0)
           // ->where(['id'=>['NEQ',$anchorData['uid']]])
            ->find();
        if (is_null($user['ids']))
        {
            $this->error("no_user");
        }

        $data = [
            'msg'         => $anchorData['nickname']."刚刚发来了视频邀请",
            'type'        => 4,
            'user_id'     => $user['ids'],
            'anchor_id'   => $anchorData['uid'],
            'url'          => $anchorData['video_url'],//视频URL
            'avatar'       => $anchorData['avatar'],//头像
            'nickname'    => $anchorData['nickname']//昵称
        ];
        $push = hook_one("push_msg", $data);
        $reData = [
            'state'=>$push,
            'pdata'=>$data
        ];
        $this->success('请求成功!', $reData);
    }
    /**
     * 极光推送 - 随机获取一个推荐主播
     */
    private function getRecommend()
    {
        $sql ="select "
            .   "u.id uid,u.user_nickname nickname,u.avatar,u.sex,u.signature,u.more,a.photo,a.level,a.single_coin coin,a.video_url "
            . "from yz_live_anchor as a "
            .   "left join yz_live_room as r "
            .       "on a.user_id = r.user_id "
            .           "and r.live_end = 0 "
            .   "left join yz_live_video as v "
            .       "on a.user_id = v.anchor_id "
            .           "and v.end_time = 0 "
            .   "left join yz_user as u "
            .       "on u.id = a.user_id "
            . "where a.recommend = 1 "
            .   "and a.video_state = 1 "
            .   "and r.id is null "
            .   "and v.id is null "
            . "order by rand() asc ";
        $info = Db::query($sql);
        if (count($info) == 0)
        {
            return [
                'ret'   => false,
                'msg'   => "暂无推荐主播"
            ];
        }
        $info = $info[0];
        $more = json_decode($info['more'],true);
        $info = array_merge($info,$more);
        unset($info['more']);

        $cdnSettings    = cmf_get_option('cdn_settings');
        if ($info['avatar'] != '' &&  strpos($info['avatar'],'http://') === false){
            $info['avatar'] = 'http://'.$cdnSettings['cdn_static_url'] .$info['avatar'];
        }
        if ($info['photo'] != '' &&  strpos($info['photo'],'http://') === false){
            $info['photo'] = 'http://'.$cdnSettings['cdn_static_url'] .$info['photo'];
        }
        if ($info['video_url'] != '' &&  strpos($info['video_url'],'http://') === false){
            $info['video_url'] = 'http://'.$cdnSettings['cdn_static_url'] .$info['video_url'];
        }
        return [
            'ret'   => true,
            'msg'   => "获取成功",
            'data'  => $info
        ];
    }
    /**
     * 极光-上传资源
     */
    public function jpushImg()
    {
        $data = Db::name("live_message")->whereIn('type',[2,3])->where('jpush_media','NULL')->find();
        if (!$data)exit("finish");

        if ($data['type'] == 2) $param['type'] = "image";//图片
        elseif ($data['type'] == 3) $param['type'] = "voice";//语音
        else $param['type'] = "file";//文件

        $url = $data['message'];

        $tmp = strpos($url,'uploads/');
        if ($tmp > 0) {
            $param['url'] = "/www/wwwroot/app/public/".substr($url,$tmp);
        }
        else
        {
            $param['url'] = $url;
        }

        $result = hook_one("upload_jpush", $param);

        $data['jpush_media'] = json_encode($result['body']);
        Db::name("live_message")->update($data);
        exit("success");
    }

    /**
     * 每天早晚打招呼
     */
    public function hi()
    {
        $anchorData = Db::name("user")->alias('u')
            ->field('u.id uid,u.user_nickname nickname,u.avatar')
            ->join('live_anchor a','a.user_id=u.id')
            ->where(['a.recommend'=>1])
            ->order('rand()')->find();

        $data = [
            'msg'         => $anchorData['nickname']."刚刚和你打了个招呼，快去看看吧",
            'type'        => 1,
        ];
        $push = hook_one("push_msg", $data);
        $reData = [
            'state'=>$push,
            'pdata'=>$data
        ];
        $this->success('请求成功!', $reData);
    }
  
    /**
     * 极光-发送消息-测试
     */
    public function jpushSendTest()
    {
        $data = [
            'from_id'          => "QY_2",
            'target_id'        => "QY_1043",
            'msg_type'          => 'text',
            'text'           => '有空聊聊吗？',
        ];
        $result = hook_one("send_jpush_msg", $data);
        var_dump($result);
        exit();
    }
    /**
     * 极光用户注册
     */
    public function jpushReg()
    {
        $id = $this->request->param('id');
        $data = [
            'name'=>'QY_'.$id,
            'pass'=>'QY_'.$id.'HUHU'
        ];
        $result = hook_one("user_jpush_reg", $data);
        var_dump($result);
    }
    /**
     * 极光-发送话术
     */
    public function jpushSendMsg()
    {
        $fname = $this->request->param('fname');
        $fname = CMF_ROOT . 'data/huashu/'.$fname;
        if(!file_exists($fname))
            exit("nofile");
        $file = fopen($fname, "r");
        if(feof($file))
            exit("file error");
        $data = json_decode(fgets($file),true);
        fclose($file);

        $result = hook_one("send_jpush_msg", $data);
        exit(json_encode($result['body']));
//        $re = json_decode($result,true);
//        if (!empty($re['data'])) exit('succ');
//        else exit("fail");
    }
    /**
     * 推送视频通话
     */
    public function jpushAnchor(){
        $fname = $this->request->param('fname');
        $fname = CMF_ROOT . 'data/video/'.$fname;
        if(!file_exists($fname))
            exit("nofile");
        $file = fopen($fname, "r");
        if(feof($file))
            exit("file error");
        $data = json_decode(fgets($file),true);
      //var_dump($data);
      //var_dump("======================");
        fclose($file);
        $push = hook_one("push_msg", $data);
        exit(json_encode($push));
        //exit("end");
    }
  

    /**
     * 环信 - 发送消息--测试
     */
    public function huanxinSendTest()
    {
        $data = [
            'target_type'   => 'users',
            'target'        => ['2518','1308'],
            'msg'           => [
                'type'  => 'txt',
                'msg'   => '有空聊聊吗？'
            ],
            'from'          => '872'
        ];
        echo json_encode($data);
        $result = hook_one("send_easemob", $data);
        //var_dump($result);
        exit();
    }
    /**
     * 环信 - 发送话术
     */
    public function huanxinSend()
    {
        $now = time();
        //获取被推送用户ID列表
        $uids = Db::name('user')
            ->where(['last_login_time'=>['>=',$now-120]])//2分钟内登录的，有推送
            ->where('is_zombie','0')
            ->where('is_virtual','0')
            ->order('last_login_time asc')//按照登录时间排序
            ->limit(20)//取前20个
            ->column('id');
        if (count($uids) == 0)
        {
            exit("end");
        }
//        var_dump( Db::name('user')->getLastSql());
//        var_dump($uids);

        //随机获取一个推送话术用户信息
        $user = Db::name('live_message')->alias('m')->join('yz_user u','m.user_id=u.id')->field('u.*')->order("rand()")->find();

        //获取用户话术
        $msg = Db::name('live_message')->where('user_id',$user['id'])->whereIn('type',['1'])->order('id asc')->limit(2)->select();
//        var_dump($msg);

        $data =[
            'username'  => $user['id'],
            'password'  => "huhunihao" . $user['id']
        ];
        $result_r = hook_one("register_easemob", $data);
//        var_dump($result_r);
//        var_dump("*****************************");

        $data = [
            'target_type'   => 'users',
            'target'        => $uids,
            'msg'           => [
                'type'  => 'txt',
                'msg'   => $msg[0]['message']
            ],
            'from'          => $user['id']
        ];
//        var_dump($data);
//        var_dump("*****************************");

        $result = hook_one("send_easemob", $data);
//        var_dump($result);
//        var_dump("*****************************");
        if (count($msg) == 1)//没有下一句，返回end
        {
            exit("end");
        } else { //有下一句，返回用户ID组，话术用户ID，下一条消息ID，间隔时间
            $second = $msg[1]['second'] == 0 ? 10 : $msg[1]['second'];
            exit(json_encode($uids).'|'.$user['id'].'|'.$msg[1]['id'].'|'.$second);
        }
    }
    /**
     * 环信 - 后续话术发送
     */
    public function huanxinSend2()
    {
        $data = $this->request->param('data');
        $data1 = explode('|',$data);

        $uids       = $data1[0];
        $user_id    = $data1[1];
        $msg_id     = $data1[2];

        //获取用户话术
        $msg = Db::name('live_message')->where('user_id',$user_id)->whereIn('type',['1'])->where(['id'=>['>=',$msg_id]])->order('id asc')->limit(2)->select();

        $dataSend = [
            'target_type'   => 'users',
            'target'        => json_decode($uids,true),
            'msg'           => [
                'type'  => 'txt',
                'msg'   => $msg[0]['message']
            ],
            'from'          => $user_id
        ];
      
        $result = hook_one("send_easemob", $dataSend);
        if (count($msg) == 1)//没有下一句，返回end
        {
            exit("end");
        } else { //有下一句，返回用户ID组，话术用户ID，下一条消息ID，间隔时间
            $second = $msg[1]['second'] == 0 ? 10 : $msg[1]['second'];
            exit($uids.'|'.$user_id.'|'.$msg[1]['id'].'|'.$second);
        }
    }
    /**
     * 环信 - 计划任务发送消息
     */
    public function huanxinSendMsg()
    {
        $fname = $this->request->param('fname');
        $fname = CMF_ROOT . 'data/huashu/'.$fname;
        if(!file_exists($fname))
            exit("nofile");
        $file = fopen($fname, "r");
        if(feof($file))
            exit("file error");
        $data = json_decode(fgets($file),true);
        fclose($file);
        $result = hook_one("send_easemob", $data);
        $re = json_decode($result,true);
        if (!empty($re['data'])) exit('succ');
        else exit("fail");
    }
    /**
     * 环信 - 上传文件
     */
    public function huanxinFile()
    {
//        $file = '@/www/wwwroot/app/public/cpsdata/img/a1.jpg';
        $file = parse_url('http://imgs.jiaoyou0.cn/uploads/photo/2018/10/5bb73ed664d69.jpg');
        $file = pathinfo($file['path']);
        $result = hook_one("fiels_easemob", $file);
        exit();
    }
}