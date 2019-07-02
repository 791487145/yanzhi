<?php
// +----------------------------------------------------------------------
// | 虎虎网络科技
// +----------------------------------------------------------------------
// | 视频通话类
// +----------------------------------------------------------------------
// | Author: lixue
// +----------------------------------------------------------------------
namespace api\broadcast\controller;

use think\Db;
use think\Exception;
use cmf\controller\RestUserBaseController;
use think\Cache;
use api\broadcast\service\PayConsumeModel;

class VideocallController extends RestUserBaseController
{
    /**
     * 申请视频聊天
     */
    public function create()
    {
        $id = $this->request->post('id', 0, 'intval');//接收方用户ID

        //判断对方用户状态
        $user = Db::name('user')->where('id',$id)->where('user_type',2)->where('user_status',1)->find();
        if (!$user)
        {
            $this->error("用户信息错误");
        }
        //判断对方是否主播
        $anchor = Db::name("live_anchor")->where('user_id',$id)->where('status',1)->find();
        if (!$anchor)
        {
            $this->error("此用户不是主播");
        }
        if ($this->user['vip'] < time())
        {
            $this->error("请先升级VIP用户",'',[],-3);
        }
        if ($this->user['balance'] < $anchor['single_coin'])
        {
            $this->error("余额不足",'',[],-2);
        }
        //判断是否虚拟主播
        if ($user['is_zombie'] == 1 || $user['is_virtual'] == 1)//如果是，则返回正在视频通话
        {
            $this->error($user['user_nickname']."正在视频通话");
        }
        //判断直播状态
        $live = Db::name("live_room")->where('user_id',$id)->where('live_state',1)->find();
        if ($live)
        {
            $this->error($user['user_nickname']."正在直播");
        }
        //判断视频通话状态
        $video = Db::name("live_video")->where('anchor_id',$id)->where('end_time',0)->find();
        if($video){
            $this->error($user['user_nickname']."正在视频通话");
        }

        //推送申请消息
        $data = [
            'msg'       => $this->user['user_nickname']."请求与您视频通话",
            'type'      => 3,
            'user_id'   => $id,
            'room'      => $id."_".$this->userId
        ];
        $push = hook_one("push_msg", $data);
        if ($push)
        {
            $this->success("等待对方接听");
        }
        else
        {
            $this->error("请求失败");
        }
    }

    /**
     * 开始聊天
     */
    public function start()
    {
        $room = $this->request->post('room');//接收房间信息
        //解析用户ID
        $users = explode("_",$room);
        if ($users[0] != $this->userId)
        {
            $this->error("房间信息有误");
        }

        //判断视频聊天房间状态-----前端进行判断

        //获取用户私播单价
        $anchor = Db::name("live_anchor")->where('user_id',$this->userId)->find();

        //创建视频聊天记录
        $re = Db::name("live_video")->insert([
            'room'              => $room,
            'anchor_id'         => $users[0],
            'user_id'           => $users[1],
            'unit_coin'         => $anchor['single_coin'],
            'start_time'        => time()
        ]);

        if ($re)
        {
            $this->success("视频接通成功");
        }
        else
        {
            $this->error("视频接通失败");
        }
    }

    /**
     * 结束聊天
     */
    public function stop() {
        $room = $this->request->post('room');//接收房间信息

        //判断视频通话状态
        $video = Db::name("live_video")->where('room',$room)->order("id desc")->find();
        if(!$video) {
            $this->error("房间信息错误");
        }
        if ($video['end_time'] > 0) {//视频通话已经结束
            $reData = [
                "minute"    => $video['nums'],
                "coin"      => $video['coin']
            ];
            $this->success("视频通话已经结束",$reData);
        }

        $video['end_time'] = time();
        $video['nums'] = ceil(($video['end_time'] - $video['start_time']) / 60);
        $video['coin'] = $video['unit_coin'] * $video['nums'];

        $reData = $this->jiesuan($video);
        $this->success("视频通话结束",$reData);
    }

    /**
     * 判断余额
     */
    public function check()
    {
        $room = $this->request->post('room');//接收房间信息
        //判断视频通话状态
        $video = Db::name("live_video")->where('room',$room)->where('end_time',0)->find();
        if(!$video) {
            $this->error("视频通话已经结束");
        }
        $video['end_time'] = time();
        $video['nums'] = ceil(($video['end_time'] - $video['start_time']) / 60);
        $video['coin'] = $video['unit_coin'] * $video['nums'];
        //获取用户余额
        $user = Db::name("user")->where('id',$video['user_id'])->find();
        if ($user['balance'] < ($video['coin'] + $video['unit_coin'])) {
            $reData = $this->jiesuan($video);
            $this->error("余额不足,通话结束",$reData,[],-2);
        } else {
            $this->success("余额充足");
        }
    }

    /**
     * 结算
     * @param $video
     * @return array
     * @throws Exception
     */
    private function jiesuan($video) {
        //更新视频通话表信息
        Db::name("live_video")->update($video);
        //生成消费订单号
        $sn = create_sn($video['end_time'] , "pay_consume");
        //消费用户信息
        $user = Db::name("user")->where('id',$video['user_id'])->find();
        //主播信息
        $anchor = Db::name("live_anchor")->where('user_id',$video['anchor_id'])->find();

        //消费订单数据
        $data = [
            'user_id'       => $video['user_id'],
            'anchor_id'     => $video['anchor_id'],
            'room_id'       => $video['id'],
            'sn'             => $sn,
            'type'           => 3,//视频聊天
            'coin'           => $video['coin'],
            'add_time'      => $video['end_time'],
            'ip'             => get_client_ip(),
            'gift_id'       => 0,
            'gift_num'      => $video['nums']
        ];

        $pModel = new PayConsumeModel();
        $re = $pModel->consume($data, $anchor, $user['balance']);
        if ($re['ret']) {
            return [
                "minute"     => $video['nums'],
                "coin"      => $video['coin']
            ];
        } else {
            $this->error("结算失败，请重试");
        }
    }
}
