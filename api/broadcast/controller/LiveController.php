<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\broadcast\controller;

use think\Db;
use think\Validate;
use cmf\controller\RestUserBaseController;
use think\cache\driver\Redis;
use think\Cache;
use api\broadcast\service\PayConsumeModel;

class LiveController extends RestUserBaseController
{
    /**
     * 申请成为主播
     */
    public function apply(){
        $user = $this->user;
        //判断主播审核状态
        $anchor = Db::name('live_anchor')->where(['user_id'=>$user['id']])->find();
        if ($anchor)
        {
            if ($anchor['status'] == 0){
                $this->error("您的申请正在审核中，请耐心等待");
            }
            if  ($anchor['status'] == 1){
                $this->error("您已通过审核");
            }
        }
        $validate = new Validate([
            'name'      => 'require|min:2|max:4',
            'idnum'     => 'require|length:18',
            'photo'     => 'require',
        ]);
        $validate->message([
            'name.require'     => '请输入您的姓名!',
            'name.max'         => '姓名不能超过4个字',
            'name.min'         => '姓名不能小于2个字',
            'idnum.require'    => '请输入您的身份证号!',
            'idnum.length'     => '请输入18位身份证号',
            'photo.require'    => '请上传相册照片!',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if (!$this->checkIdCard($data['idnum'])){
            $this->error("身份证号错误");
        }

        $idUrl = $this->getIdUrl($this->userId);
        $cardPic = ROOT_PATH . 'public' . DS . 'upload' . DS . 'card' . DS . $idUrl[0] . DS . $idUrl[1] . DS . $idUrl[2] . '.jpg';
        if(!file_exists($cardPic)){
            $this->error("请上传手持身份证照片");
        }
        if (count($data['photo']) < 2){
            $this->error("请上传至少两张生活照片");
        }

        $guideId = 0;
        if (isset($data['guide']) && $data['guide'] != '')//判断工会信息
        {
            $guide = Db::name('guide')->where(['code'=>$data['guide']])->find();
            if (!$guide){
                $this->error("工会不存在");
            }
            if ($guide['status'] != 1){
                $this->error("工会状态错误");
            }
            $guideId = $guide['id'];
        }

        $time = time();
        //主播相册处理
        $userPhoto = Db::name('user_photo')->where(['user_id'=>$this->userId])->select();
        //用户上传过的图片
        $userPic = Db::name('asset')->where(['user_id'=>$this->userId])->select();

        $insertPhoto = $arrUserPic = [];

        foreach ($userPic as $item) {
            $arrUserPic[] = $item['file_path'];
        }

        foreach ($data['photo'] as $i => $purl){
            if(!in_array($purl,$arrUserPic)){
                $this->error("相册图片信息错误，".$purl);
            }
            if (isset($userPhoto[$i])){//已有排序的照片信息
                if ($purl != $userPhoto[$i]['url']){//照片信息变更
                    Db::name('user_photo')->where(['id'=>$userPhoto[$i]['id']])->update([
                        'url'       => $purl,
                        'add_time'   => $time,
                        'is_vip'    => 0
                    ]);
                }
            }else{//没有排序的新增
                $insertPhoto[] = [
                    'user_id'   => $this->userId,
                    'url'       => $purl,
                    'add_time'   => $time,
                    'is_vip'    => 0
                ];
            }
        }
        if (count($insertPhoto) > 0){
            Db::name('user_photo')->insertAll($insertPhoto);
        }

        if ($anchor){//已有主播申请，修改申请信息
            $updateData = [
                'guide_id'      => $guideId,
                'add_time'      => $time,
                'audit_time'    => 0,
                'status'        => 0,
            ];
            Db::name('live_anchor')->where(['user_id'=>$anchor['user_id']])->update($updateData);
        }else{//添加主播申请记录
            Db::name('live_anchor')->insert([
                'user_id'       => $this->userId,
                'guide_id'      => $guideId,
                'add_time'      => $time,
            ]);
        }

        //更新用户实名信息
        Db::name('user')->where(['id'=>$user['id']])->update(['truename'=>$data['name'],'idnum'=>$data['idnum']]);

        $this->success("提交成功，请等待管理员审核");
    }
    
    /**
     * 创建直播间
     * http://yanzhi.s1.natapp.cc/api/broadcast/live/create
     * userid=2&name=123&device_type=mobile&sign=eab63c0b4fe657bf082ff4c32bf7c1c4
     */
    public function create()
    {
        $user = $this->user;
        //判断主播审核状态
        $anchor = Db::name('live_anchor')->where(['user_id'=>$user['id']])->find();
        if (!$anchor)
        {
            $this->error("你还不是主播");
        }
        if ($anchor['status'] != 1){
            $this->error("您尚未通过审核，请耐心等待");
        }

        $now = time();

        //将直播中的房间结束
        Db::name('live_room')->where(['user_id'=>$user['id'],'live_state'=>1])->update(['live_state'=>2,'live_end'=>$now]);

        $type = $this->request->post('type', 0, 'intval');
        $money = $this->request->post('money', 0, 'intval');
        $data = $this->request->post();
        $validate = new Validate([
            'name'      => 'require|min:2|max:10',
        ]);
        $validate->message([
            'name.require'      => '请输入直播间名称!',
            'name.max'          => '直播间名称不能超过10个字符',
            'name.min'          => '直播间名称不能小于2个字符',
        ]);
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if ($type == 6)//私播时，收费标准必填
        {
            if ($money <= 0){
                $this->error('请填写收费标准');
            }
            if ($money != $anchor['single_coin']){
                Db::name('live_anchor')->where(['user_id'=>$user['id']])->update([ 'single_coin' => $money ]);
            }
        }

        //调用接口获取推流地址
        $live_code = $user['id']."_".$now;
        $reData = hook_one("get_live_video_push_url", $live_code);
        if ($reData === false) {
            $this->error('未安装插件,请联系管理员!');
        }
        elseif (!empty($reData['error']))
        {
            $this->error($reData['message']);
        }

        //主播信息
        $reData['votestotal'] = $anchor['gift_total'];//主播当前获取的礼物价值
        $reData['level'] = $anchor['level']; // 主播等级

        //其他信息
        $broadcastSetting = cmf_get_option('broadcast_settings');
        $reData['userlist_time'] = $broadcastSetting['userlist_time'];
        $reData['chatserver'] = $broadcastSetting['chatserver'];
        $reData['barrage_fee'] = $broadcastSetting['barrage_fee'];
        $reData['shut_time'] = $broadcastSetting['shut_time'];
        $reData['kick_time'] = $broadcastSetting['kick_time'];

        //直播间信息入库
        $saveData = [
            'user_id'       => $user['id'],
            'live_title'    => $data['name'],
            'live_create'   => $now,
            'live_start'    => $now,//暂时创建即开播，后续改为创建后，主播点击开播
            'live_end'      => 0,
            'live_state'    => 1,
            'live_code'     => $reData['stream']
        ];
        $re = Db::name('live_room')->insert($saveData);
        if (!$re){
            $this->error("房间创建失败，请重试");
        }

        $redisData = [
            'id'                => $user['id'],
            'user_nicename'   => $user['user_nickname'],
            'avatar'           => $user['avatar'],
            'sex'              => $user['sex'],
            'signature'        => $user['signature'],
            'experience'       => '',
            'consumption'      => '',
            'votestotal'       => $anchor['gift_total'],
            'province'         => '',
            'city'             => '',
            'isrecommend'     => $anchor['recommend'],
            'showid'           => $reData['stream'],
            'starttime'        => $saveData['live_create'],
            'title'            => $saveData['live_title']
        ];
        $redis =new Redis();   //实例化
        $redis->handler()->hset('livelist',$user['id'],json_encode($redisData));

        $this->success("创建成功",$reData);
    }

    /**
     * 开始直播
     */
    public function start()
    {

    }

    /**
     * 结束直播
     */
    public function stop()
    {
        //获取当前主播最后一次直播房间记录
        $room = Db::name('live_room')->where('user_id',$this->userId)->order('id desc')->find();
        if ($room['live_state'] == 0){
            $this->error("直播尚未开始");
        }
        if ($room['live_state'] == 2){
            $this->error("直播已经结束");
        }

        $room['live_state'] = 2;
        $room['live_end'] = time();
        //修改房间状态及关闭时间
        $re = Db::name('live_room')->update($room);
        if (!$re)
        {
            $this->error("操作失败，请重试");
        }
        $result = hook_one("set_live_video_stop", $room['live_code']);

        //判断房间中是否有用户
        $live_user = Db::name("live_user")->where('anchor_id',$this->userId)->where('exit_time',0)->find();
        if ($live_user)//有用户时
        {
            if ($room['live_type'] == 6)//私播房间，做结算
            {
                //观众用户信息
                $userInfo = Db::name("user")->where('id',$live_user['user_id'])->find();

                //更新用户退出房间时间信息
                $live_user['exit_time'] = $room['live_end'];
                Db::name('live_user')->update($live_user);

                $num = ceil(( $live_user['exit_time'] - $live_user['join_time'] ) / 60);//以分钟计费，向上取整
                //=========== 交易订单处理 start ================
                $data = [
                    'user_id'       => $live_user['user_id'],
                    'anchor_id'     => $live_user['anchor_id'],
                    'room_id'       => $room['id'],
                    'channel_id'    => $userInfo['channel_id'],
                    'type'           => 2,
                    'gift_id'       => 0,
                    'gift_num'      => $num,
                    'add_time'      => $live_user['exit_time']
                ];
                $order = $this->CoinUpdate($data, $this->user['balance']);
                //=========== 交易订单处理 end ================
                $room['total_coin'] += $order['coin'];//房间已结算金额 + 当前私播观众消费金额
            }
            else//直播房间，更新所有用户结束状态
            {
                Db::name('live_user')->where('anchor_id',$this->userId)->where('exit_time',0)->update(['exit_time'=>$room['live_end']]);
            }
        }

        $reData = [
            'live_time'     => $room['live_end'] - $room['live_start'],//直播时长，单位：秒
            'user_total'    => $room['aud_total'],//进入直播间的观众总数量
            'gift_total'    => $room['total_coin']//直播期间收礼物总价值//私播期间用户送礼总价值
        ];

        //删除redis缓存
        $redis =new Redis();   //实例化
        $redis->handler()->hdel('livelist',$this->userId);

        $this->success("直播结束",$reData);
    }

    /**
     * 直播间列表
     */
    public function rooms()
    {
        $data = $this->request->post();
        if (!$data['page'])$data['page'] = 0;
        $where = ['r.live_state' => 1,'recom_type'=>['exp','is not null']];//只获取直播中的，非首页推荐的
        $order = "r.id desc";
        switch ($data['type']){
            case "hot": //热门主播
                $order = 'r.audience desc';//按照当前观众数量但需排列
                break;
            case "new": //最新开播
                $order = 'r.live_start desc';
                break;
            case "recom": //推荐主播
                $where['a.recommend'] = 1;
                $order = 'a.recom_time desc';
                break;
            case "priv": //私播
                $where['r.live_type'] = 6;
                $order = 'a.video_recom desc,r.id desc';
                break;
            default: //
        }
        $list = Db::name('live_room')
            ->alias("r")
            ->field('r.*,a.single_coin,a.gift_total,a.level,u.user_nickname,u.sex,u.avatar,u.signature,u.birthday,u.more,u.city,u.province')
            ->join('__USER__ u', 'r.user_id =u.id')
            ->join('yz_live_anchor a', 'r.user_id =a.user_id')
            ->where($where)
            ->order($order)->limit($data['page']*10,10)->select();
        if (count($list) == 0){
            $this->error('暂无数据');
        }
        $reData = [];
        foreach ($list as $value){
            if ($value['avatar'] != '' &&  strpos($value['avatar'],'http://') === false){
                $value['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$value['avatar'];
            }
            $age = floor( ( time() - $value['birthday'] ) / 86400 / 365 );
            $more = json_decode($value['more'],true);

            $tmp = [
                'user_id'       => $value['user_id'],
                'title'         => $value['live_title'],
                'start'         => $value['live_start'],
                'audience'      => $value['audience'],
                'sex'           => $value['sex'],
                'nickname'      => $value['user_nickname'],
                'avatar'        => $value['avatar'],
                'votestotal'   => $value['gift_total'],
                'type'          => $value['live_type'],
                'type_val'      => $value['single_coin'],
                'age'           => $age,
                'job'           => empty($more['job'])?'':$more['job'],
                'province'     => empty($value['province'])?'':$value['province'],
                'city'          => empty($value['city'])?'':$value['city'],
                'tag'           => $value['live_tag']
            ];
            if ($tmp['tag'] != ''){
                $tmp['tag'] = 'http://' . $_SERVER['HTTP_HOST'] . '/upload/tag/' . $tmp['tag'] . '.png';
            }

            $reData[] = $tmp;
        }
        $this->success("succ",$reData);
    }

    /**
     * 直播间观众列表
     */
    public function users()
    {
        $validate = new Validate([
            'id'      => 'require',
        ]);
        $validate->message([
            'id.require'      => '参数错误!'
        ]);
        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $id = $data['id'];

        $users = Db::name('live_user')->alias('l')
            ->join('__USER__ u', 'l.user_id =u.id')
            ->field('l.user_id,u.user_nickname nickname,u.avatar,u.sex,u.coin,l.join_time,l.times,l.point,l.gift_total gift,l.shut_endtime')
            ->where([
                'anchor_id' => $id,
                'exit_time' => 0,
                'status'    => 0
            ])
            ->order('u.is_zombie asc,u.is_virtual asc,join_time asc')
            ->select();

        foreach ($users as $key => $value){
            if ($value['avatar'] != '' &&  strpos($value['avatar'],'http') === false){
                $value['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$value['avatar'];
                $users[$key] = $value;
            }
        }

        $this->success("succ",$users);
    }

    /**
     * 获取直播间播放地址，进入直播间
     *  http://www.yanzhi.com/api/broadcast/live/play
     */
    public function play()
    {
        $id = $this->request->post('id', 0, 'intval');
        if ($id == 0) {
            $this->error("参数错误");
        }
        if ($id == $this->userId) {
            $this->error('这是您自己的直播间');
        }

        //判断播放状态
        $room = Db::name('live_room')->where(['user_id'=>$id])->order('id desc')->find();
        if (!$room)//无主播房间信息
        {
            $this->error("直播间尚未开启");
        }
        if ($room['live_state'] == 0){
            $this->error("主播正在准备，请稍等");
        }
        if ($room['live_state'] == 2){
            $this->error("直播结束");
        }

        //判断聊天服务器是否连通
        $redis =new Redis();   //实例化
        $chatserver = $redis->handler()->hget('livelist',$id);
        if (!$chatserver)//聊天服务器未连通，则表示直播结束
        {
            Db::name('live_room')->where(['id'=>$room['id']])->update(['live_state'=>2,'live_end'=>time()]);
            $this->error("直播结束");
        }

        if ($room['live_url'] == '')//判断是否僵尸主播
        {
            //调用接口获取推流地址，并判断直播流状态
            $result = hook_one("get_live_video_play_url", $room['live_code']);
            if (!$result['ret'])
            {
                $this->error($result['msg']);
            }
            else
            {
                $result = $result['data'];
            }
        }
        else
        {
            $result = [
                'stream'    => $room['live_code'],
                'url'       => $room['live_url']
            ];
        }

        //将观看数据写入数据库
        $liveUser = Db::name('live_user')->where(['anchor_id'=> $id,'user_id'=>$this->userId])->find();
        if ($liveUser){
            Db::name('live_user')->where(['id'=>$liveUser['id']])->update([
                'join_time' =>time(),
                'exit_time' => 0,
                'times'     => $liveUser['times'] + 1
            ]);
            if ($liveUser['join_time'] <= $room['live_create'])//首次进入新房间，房间总计观众数+1
            {
                Db::name('live_room')->where(['id'=>$room['id']])->setInc('aud_total',1);
            }
        }else{
            Db::name('live_user')->insert([
                'anchor_id' => $id,
                'user_id'   =>$this->userId,
                'join_time' =>time(),
                'exit_time' => 0,
                'times'     => 1
            ]);
            Db::name('live_room')->where(['id'=>$room['id']])->setInc('aud_total',1);
        }

        //获取用户关注状态
        $follow = Db::name('user_follow')->where(['user_id'=> $id,'fans_id'=>$this->userId])->find();
        if (!$follow)
        {
            $result['follow'] = 0;
        }
        else
        {
            $result['follow'] = $follow['status'];
        }

        //其他信息
        $broadcastSetting = cmf_get_option('broadcast_settings');
        $result['userlist_time'] = $broadcastSetting['userlist_time'];
        $result['chatserver'] = $broadcastSetting['chatserver'];
        $result['barrage_fee'] = $broadcastSetting['barrage_fee'];
        $result['shut_time'] = $broadcastSetting['shut_time'];
        $result['kick_time'] = $broadcastSetting['kick_time'];

        $this->success("获取成功",$result);
    }

    /**
     * 退出房间
     */
    public function out()
    {
        $live_code = $this->request->post('stream');
        //判断房间及用户信息
        $check = $this->checkRoom($live_code);
        if (!$check['ret'])
        {
            $this->error($check['msg']);
        }
        $roomInfo = $check['room'];
        $live_user = $check['live_user'];

        $live_user['exit_time'] = time();
        //更新用户退出房间时间信息
        Db::name('live_user')->update($live_user);
        $reData = ['balance' => $this->user['balance']];

        if ($roomInfo['live_type'] == 6)//私播房间需要计算结算金额
        {
            $num = ceil(( $live_user['exit_time'] - $live_user['join_time'] ) / 60);//以分钟计费，向上取整
            //=========== 交易订单处理 start ================
            $data = [
                'user_id'       => $this->userId,
                'anchor_id'     => $roomInfo['user_id'],
                'room_id'       => $roomInfo['id'],
                'channel_id'    => $this->user['channel_id'],
                'type'           => 2,
                'gift_id'       => 0,
                'gift_num'      => $num,
                'add_time'      => $live_user['exit_time']
            ];
            $order = $this->CoinUpdate($data, $this->user['balance']);
            //=========== 交易订单处理 end ================
            $reData['balance'] = $order['balance'];
        }

        $this->success("退出成功",$reData);
    }

    /**
     * 赠送礼物
     * @throws \think\Exception
     */
    public function gift()
    {
        $gift       = $this->request->post('gift', 0, 'intval');
        $gift_num   = $this->request->post('num', 0, 'intval');
        $longlink   = $this->request->post('longlink',1, 'intval');
        $live_code  = $this->request->post('stream','');
        $userId     = $this->request->post('user_id',0, 'intval');

        //获取赠送礼物的信息
        $giftInfo = Db::name("gift")->where(['id'=>$gift])->find();
        if (!$giftInfo) {
            $this->error("礼物信息错误");
        }
        if ($gift_num <= 0) {
            $this->error("礼物数量有误");
        }
        $coin = $giftInfo['coin'] * $gift_num;      //赠送礼物价值
        if ( $coin > $this->user['balance'] ) {
            $this->error("余额不足",'',[],-2);
        }

        //=========== 交易订单处理 start ================
        $now = time();
        $sn = create_sn($now , "pay_consume");
        $data = [
            'user_id'       => $this->userId,
            'anchor_id'     => $userId,
            'room_id'       => 0,
            'sn'             => $sn,
            'type'           => 1,
            'coin'           => $coin,
            'add_time'      => $now,
            'ip'            => get_client_ip(),
            'gift_id'       => $gift,
            'gift_num'      => $gift_num
        ];

        if ($longlink == 1) {//直播间赠送礼物时，判断房间及用户信息
            $check = $this->checkRoom($live_code);
            if (!$check['ret']) {
                $this->error($check['msg']);
            }
            $roomInfo = $check['room'];
            $data['anchor_id'] = $roomInfo['user_id'];
            $data['room_id'] = $roomInfo['id'];
        }
        //主播信息
        $anchor = Db::name("live_anchor")->where('user_id',$userId)->find();

        $pModel = new PayConsumeModel();
        $re = $pModel->consume($data, $anchor, $this->user['balance']);
        if (!$re['ret']) {
            $this->error("赠送失败");
           // $this->error("赠送失败:".$re['message']);
        }
        //=========== 交易订单处理 end ================

        $giftToken = "gift_".$sn;
        //=========== redis信息处理 start ================直播间送礼物需要将数据写入redis
        if ($longlink == 1) {
            $avatar = $this->user['avatar'];
            if ($avatar != '' &&  strpos($avatar,'http://') === false) {
                $avatar = 'http://'.$_SERVER['HTTP_HOST'] .$avatar;
            }

            $redisData = [
                "uid"    => $this->userId,
                "touid"    => $roomInfo['user_id'],
                "giftid"    => $gift,
                "giftcount"    => $gift_num,
                "totalcoin"    => $coin,
                "showid"    => $roomInfo['id'],
                "addtime"    => $now,
                "gifttoken"    => $giftToken,
                "giftname"    => $giftInfo['name'],
                "gifticon"    => 'http://'.$_SERVER['HTTP_HOST'] .$giftInfo['pic'],
                "nicename"=> $this->user['user_nickname'],
                "avatar"=> $avatar,
                "type"=> "1",                              // 测试数据
                "action"=> "1",                           // 测试数据
                "level"=> 10,                            // 测试数据
            ];

            Cache::store('redis')->set($giftToken,json_encode($redisData));
        }
        //=========== redis信息处理 end ================

        //接口反馈信息
        $reData = [
            'balance'       => $re['balance'],
            'gift_token'    => $giftToken
        ];
        $this->success("赠送成功",$reData);
    }

    /**
     * 判断直播间状态，并获取观看状态信息
     * @param $stream
     * @return array
     */
    private function checkRoom($stream)
    {
        $roomInfo = Db::name('live_room')->where(['live_code'=>$stream])->find();
        if (!$roomInfo) {
            return [ 'ret' => false , 'msg' => "房间信息错误" ];
        }
        $live_user = Db::name('live_user')->where(['anchor_id'=>$roomInfo['user_id'],'user_id'=>$this->userId])->find();
        if (!$live_user) {
            return [ 'ret' => false , 'msg' => "请先进入直播间" ];
        }
        if ($live_user['exit_time'] > 0) {
            return [ 'ret' => false , 'msg' => "您已经退出房间" ];
        }
        return [ 'ret' => true , 'room' => $roomInfo , 'live_user' => $live_user ];
    }

    /**
     * 发送弹幕
     */
    public function barrage()
    {
        $live_code = $this->request->post('stream');
        $info = $this->request->post('info');

        //判断房间及用户信息
        $check = $this->checkRoom($live_code);
        if (!$check['ret']) {
            $this->error($check['msg']);
        }
        $roomInfo = $check['room'];

        //获取弹幕价格信息//判断账户余额
        $appSetting = cmf_get_option('broadcast_settings');
        $coin = $appSetting['barrage_fee'];
        if ($coin > $this->user['balance']) {
            $this->error("余额不足",'',[],-2);
        }

        //=========== 交易订单处理 start ================
        $now = time();
        $sn = create_sn($now , "pay_consume");
        $data = [
            'user_id'       => $this->userId,
            'anchor_id'     => $roomInfo['user_id'],
            'room_id'       => $roomInfo['id'],
            'sn'             => $sn,
            'type'           => 4,
            'coin'           => $coin,
            'add_time'      => $now,
            'ip'            => get_client_ip(),
            'gift_id'       => 0,
            'gift_num'      => 1,
            'more'          => $info//弹幕内容
        ];

        //主播信息
        $anchor = Db::name("live_anchor")->where('user_id',$roomInfo['user_id'])->find();

        $pModel = new PayConsumeModel();
        $re = $pModel->consume($data, $anchor, $this->user['balance']);
        //=========== 交易订单处理 end ================

        //=========== redis信息处理 start ================
        $avatar = $this->user['avatar'];
        if ($avatar != '' &&  strpos($avatar,'http://') === false){
            $avatar = 'http://'.$_SERVER['HTTP_HOST'] .$avatar;
        }

        $barrageToken = "barrage_".$sn;
        $redisData = [
            "uid"               => $this->userId,
            "touid"             => $roomInfo['user_id'],
            "content"           => $info,
            "totalcoin"         => $coin,
            "showid"            => $roomInfo['id'],
            "addtime"           => $now,
            "barragetoken"      => $barrageToken,
            "nicename"          => $this->user['user_nickname'],
            "avatar"            => $avatar,
            "type"              => "1",                              // 测试数据
            "action"            => "1",                           // 测试数据
            "level"             => 10,                            // 测试数据
        ];
        Cache::store('redis')->set($barrageToken,json_encode($redisData));
        //=========== redis信息处理 end ================

        //接口反馈信息
        $reData = [
            'balance'           => $re['balance'],
            'barrage_token'    => $barrageToken
        ];

        $this->success("发送成功",$reData);
    }
}
