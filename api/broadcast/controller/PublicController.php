<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\broadcast\controller;

use api\broadcast\service\PayConsumeModel;
use api\model\PayPaymentModel;
use think\Db;
use think\Validate;
use cmf\controller\RestUserBaseController;
use think\cache\driver\Redis;

class PublicController extends RestUserBaseController
{
    /**
     * 举报
     */
    public function report()
    {
        $type = $this->request->post('type', 0, 'intval');
        $id = $this->request->post('id', 0, 'intval');

        $data = [
            'user_id'   => $id,
            'user_type' => $type,
            'reportor'  => $this->userId,
            'addtime'   => time()
        ];
        switch ($type)
        {
            case 1://举报主播
                //判断当前用户是否在直播间中
                $uData = Db::name("live_user")
                    ->where([
                        'anchor_id' =>$id,
                        'user_id'   =>$this->userId,
                        'exit_time' => 0
                    ])->find();
                if (!$uData)
                {
                    $this->error("举报信息错误");
                }
                break;
            case 2://举报观众
                //判断被举报人当天是否进入直播间
                $uData = Db::name("live_user")
                    ->where([
                        'anchor_id' =>$id,
                        'user_id'   =>$this->userId,
                        'join_time' => ['>=',strtotime("today")]
                    ])->find();
                if (!$uData)
                {
                    $this->error("举报信息错误");
                }
                break;
            default://举报用户
                //判断用户是否存在
                $uData = Db::name("user")->where('id',$id)->find();
                if (!$uData)
                {
                    $this->error("被举报用户不存在");
                }
        }
        //判断重复举报
        $where = $data;
        $where['addtime'] = ['>=',($data['addtime']-300)];//5分钟内不能重复举报
        $isRep = Db::name("user_report")->where($where)->find();
        if ($isRep)
        {
            $this->error("请不要重复举报");
        }
        $data['re_type'] = $this->request->post('re_type',  0, 'intval');
        $data['info'] = $this->request->post('re_info');

        $re = Db::name("user_report")->insert($data);
        if ($re)
        {
            $this->success("举报成功");
        }
        else
        {
            $this->error("举报失败");
        }
    }

    /**
     * 获取主页用户信息
     */
    public function getInfo()
    {
        $id = $this->request->post('id', 0, 'intval');

        //获取用户信息
        $userInfo = Db::name("user")->where(['id'=> $id])->find();
        if (!$userInfo)
        {
            $this->error("用户不存在");
        }
        $recomType = json_decode($userInfo['recom_type'],true);
        if (is_array($recomType) && in_array('1',$recomType))
        {
            $userInfo['province'] = $this->user['province'];
            $userInfo['city'] = $this->user['city'];
        }

        $more = json_decode($userInfo['more'],true);
        $age = "-";
        $now = time();
        if ($userInfo['birthday'] > 0)$age = floor( ( $now - $userInfo['birthday'] ) / 86400 / 365 );
        $reData = [
            'id'             => $userInfo['id'],
            'nickname'      => $userInfo['user_nickname'],
            'sex'            => $userInfo['sex'],
            'birthday'      => $userInfo['birthday'],
            'age'           => $age,
            'signature'     => $userInfo['signature'],
            'vip'           => ($userInfo['vip'] > time() ? 1 : 0),
            'coin_cost'     => ($userInfo['coin'] - $userInfo['balance']),
            'figure'        => empty($more['figure'])?'':$more['figure'],
            'job'           => empty($more['job'])?'':$more['job'],
            'topic'         => empty($more['topic'])?'':$more['topic'],
            'character'     => empty($more['character'])?'':$more['character'],
            'province'      => $userInfo['province'],
            'city'          => $userInfo['city'],
        ];
        if ($userInfo['avatar'] != '' &&  strpos($userInfo['avatar'],'http://') === false){
            $reData['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$userInfo['avatar'];
        }
        else
        {
            $reData['avatar'] = $userInfo['avatar'];
        }

        //获取主播信息
        $anchor = Db::name("live_anchor")->where(['user_id' => $id])->find();
        if (!$anchor || $anchor['status'] != 1)//不是主播
        {
            $reData['is_anchor'] = 0;
        }
        else
        {
            $reData['is_anchor']        = 1;
            $reData['anchor_level']     = $anchor['level'];
            $reData['anchor_coin']      = $anchor['single_coin'];
            $reData['anchor_gift']      = $anchor['gift_total'];
            $reData['video_online']     = $anchor['video_state'];//0不在线，1在线
            $reData['connect_per']      = $anchor['connect_per'];//视频聊天接通率

            //获取直播间信息
            $room = Db::name("live_room")->where(['user_id'=> $id])->order("id desc")->find();
            if (!$room || $room['live_state'] != 1)//直播间未开启
            {
                $reData['onlive'] = 0;
            }
            else//直播间开启中
            {
                $reData['onlive']       = 1;
                $reData['live_type']    = $room['live_type'];
                $reData['live_title']   = $room['live_title'];
                $reData['live_start']   = $room['live_start'];
                $reData['live_viewer']   = $room['audience'];
                $reData['video_online'] = 2;//直播间开启时，在线状态改为忙碌
            }

            //获取视频聊天信息
            $video = Db::name("live_video")->where(['anchor_id'=> $id,'end_time'=>0])->find();
            if ($video)
            {
                $reData['video_online'] = 3;//直播间开启时，在线状态改为忙碌
            }
        }

        //获取所有评价
        $comment = Db::name('live_comment')->where('anchor_id',$id)->order('id desc')->select();
        $arrComment = [];
        if (count($comment) > 0){
            foreach ($comment as $value)
            {
                $tmp = explode(',',$value['content']);
                foreach ($tmp as $c){
                    if (!in_array($c,$arrComment)){
                        $arrComment[] = $c;
                    }
                }
            }
        }
        $countComment = count($arrComment);
        if ($countComment > 3){
            $reComment = [];
            for ($i=0; $i<3; $i++){
                $tmp = rand(1,$countComment)-1;
                $reComment[] =$arrComment[$tmp];
                unset($arrComment[$tmp]);
                $countComment--;
                for ($j=$tmp; $j<$countComment; $j++){
                    $arrComment[$j] = $arrComment[$j+1];
                }
            }
            $reData['comment'] = $reComment;
        }else{
            $reData['comment'] = $arrComment;
        }

        //判断是否关注此用户
        $follow = Db::name('user_follow')->where(['user_id|fans_id'=>$id,'status'=>1])->select();
        $fansNum = $followNum = $isFollow = $isFans = 0;
        foreach ($follow as $v)
        {
            if($v['user_id'] == $id)
            {
                $fansNum ++;
                if ($v['fans_id'] == $this->userId)//当前用户已关注该用户
                {
                    $isFans = 1;
                }
            }
            if($v['fans_id'] == $id)
            {
                $followNum ++;
                if ($v['user_id'] == $this->userId)//该用户已关注当前用户
                {
                    $isFollow = 1;
                }
            }
        }
        $reData['follow_num'] = $followNum;
        $reData['fans_num'] = $fansNum;
        $reData['is_follow'] = $isFollow;
        $reData['is_fans'] = $isFans;

        //获取互相消费平台币数量
        $coin = Db::query("select id,sn,user_id,anchor_id,coin from yz_pay_consume where (user_id='$id' and anchor_id='$this->userId') or (anchor_id='$id' and user_id='$this->userId')");
        $coinSend = $coinGet = 0;
        foreach ($coin as $v)
        {
            if ($v['user_id'] == $id)
            {
                $coinGet += $v['coin'];
            }
            if ($v['anchor_id'] == $id)
            {
                $coinSend += $v['coin'];
            }
        }
        $reData['coin_send'] = $coinSend;
        $reData['coin_get'] = $coinGet;

        //判断是否VIP用户
        if ($this->user['vip'] < $now){
            //生成话术
            $this->createMsg($this->userId,$now);
            //生成视频
            $this->createVideo($this->userId,$now,$userInfo['id']);
        }

        $this->success("获取成功",$reData);
    }
    /**
     * 获取主页相册
     */
    public function photo()
    {
        $id = $this->request->post('id', 0, 'intval');
        $page = $this->request->post('page', 1, 'intval');
        $pageSize = 10;
        $start = ($page - 1) * $pageSize;

        if ($id == 0)//不传ID，则获取当前登录用户的相册
        {
            $where = ['user_id'=>$this->userId];
        }
        else
        {
            $where = ['user_id'=>$id,'status'=>1];
        }

        $list = Db::name("user_photo")
            ->where($where)
            ->order("id asc")
            ->limit($start,$pageSize)
            ->select();
        $reList = [];
        foreach ($list as $value)
        {
            $url = $value['url'];
            if ($url != '' &&  strpos($url,'http://') === false){
                $url = 'http://'.$_SERVER['HTTP_HOST'] .$url;
            }
//            if ($value['is_vip'] == 1 && $this->user['vip'] < time())
//            {
//                $url = "";
//            }
            $reList[] = [
                'is_vip'    => $value['is_vip'],
                'url'       => $url,
                'status'    => $value['status']
            ];
        }
        $this->success("获取成功",$reList);
    }
    /**
     * 获取主页视频
     */
    public function video()
    {
        $id = $this->request->post('id', 0, 'intval');
        $page = $this->request->post('page', 1, 'intval');
        $pageSize = 10;
        $start = ($page - 1) * $pageSize;

        if ($id == 0)//不传ID，则获取当前登录用户的视频
        {
            $where = ['user_id'=>$this->userId];
        }
        else
        {
            $where = ['user_id'=>$id,'status'=>1];
        }

        $list = Db::name("user_video")
            ->where($where)
            ->order("id asc")
            ->limit($start,$pageSize)
            ->select();
        $reList = [];
        foreach ($list as $value)
        {
            $url = $value['url'];
            if ($url != '' &&  strpos($url,'http://') === false){
                $url = 'http://'.$_SERVER['HTTP_HOST'] .$url;
            }
            $pic = $value['pic'];
            if ($pic != '' &&  strpos($pic,'http://') === false){
                $pic = 'http://'.$_SERVER['HTTP_HOST'] .$pic;
            }
            $reList[] = [
                'is_vip'    => $value['is_vip'],
                'url'       => $url,
                'status'    => $value['status'],
                'pic'       => $pic,
                'views'     => $value['views']
            ];
        }
        $this->success("获取成功",$reList);
    }
    /**
     * 获取主页土豪榜
     */
    public function rich()
    {
        $id = $this->request->post('id', 0, 'intval');
        $list = Db::name("pay_consume")
            ->alias("c")
            ->field('sum(c.coin) money,u.user_nickname nickname,u.sex,u.avatar')
            ->join('__USER__ u', 'c.user_id =u.id')
            ->where(['c.anchor_id'=> $id])
            ->group('c.user_id')
            ->order("money desc")
            ->limit(10)->select();
        foreach ($list as $k => $v){
            if ($v['avatar'] != '' &&  strpos($v['avatar'],'http://') === false){
                $v['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$v['avatar'];
                $list[$k] = $v;
            }
        }
        $this->success($list);

    }
  
    /**
     * 打招呼
     */
    public function hi() {
        $id = $this->request->post('id', 0, 'intval');
        if ($id == 0) {
            $this->error("操作错误");
        }
        
//        $arrMsg = [
//            '想找个贴心男友，一个人的生活实在难熬，需要一个避风港。',
//            '我很寂寞，因为需要所以放荡，哥哥敢爱，妹妹敢受。',
//            '我是个寂寞的女人，希望找到那个他排解我的寂寞，融化我。',
//            '饭在锅里，我在床上，你在哪里？',
//            '厌倦了一个人，想找个志趣相投的人，好好爱我，疼我。',
//            '等待一个真正懂得疼我的人，不论精神上还是肉体上。',
//            '爱吃麻辣烫，喜欢前戏多一点，高潮久一点。',
//            '同城一夜，不虚伪，不兜圈子，直接约。',
//            '只愿做你的女人，请你把我带到快乐的巅峰。',
//            '我喜欢泡吧，喜欢那个动感的音乐，有多么的疯狂',
//            '嗨，今晚约吗？',
//            '收到我的私信了吗？',
//            '你在干嘛呢？',
//            '我给你发了消息~！',
//            '今晚干嘛呢?',
//            '晚上一起吧！'
//        ];
//
//        $num = rand(1,count($arrMsg)) - 1;

        //发送消息的data
        $data = [
            'from_id'       => 'QY_'.$this->userId,
            'target_id'     => 'QY_'.$id,
            'msg_type'      => 'text',
//            'text'          => $arrMsg[$num],
            'text'          => '嗨~~',
        ];
        $result = hook_one("send_jpush_msg", $data);
        
        $this->success("发送成功");
    }

    /**
     * 第三方H5直播
     */
    public function otherRooms()
    {
        $user = $this->user;
        if ($user['avatar'] != '' &&  strpos($user['avatar'],'http://') === false){
            $user['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$user['avatar'];
        }
        $user['avatar'] = 'http://imgs.jiaoyou0.cn/uploads/photo/2018/06/5b22095eeb2de.png';
        $more = json_decode($user['more'],true);
        $token = $more['token'];
        //otheruid和othertoken用来验证用户合法性，用户首次访问时我们会保存自动注册一个id，同一个id每次传的值不能变，否则验证不过
        $url = "http://appstore.le8le8.cn/apptab/list.html?"
            . "otheruid=" . $this->userId                               //otheruid=你方用户ID
            . "&othertoken=" . $token                                   //othertoken=你方用户令牌
            . "&nickname=" . urlencode($user['user_nickname'])         //&nickname=用户昵称
            . "&gender=" . $user['sex']                                 //gender=用户性别值（1=男 2=女）
            . "&phonetype=" . "mobile"                                  //phonetype=手机型号
            . "&imsi=" . "1234"                                          //imsi=手机序列号
            . "&avatarurl=" . urlencode($user['avatar']);               //avatarurl=用户头像地址
        $this->success($url);
    }

    /**
     * 发送消息消费
     */
    public function sendMsgPay()
    {
        $now = time();
        //判断当前用户是否为VIP用户
        if ($this->user['vip'] < $now) {
            $this->error("您不是VIP会员",'',[],'-3');
        }

        //获取系统设置的发送消息费用//判断余额是否充足
        $appSetting = cmf_get_option('app_settings');
        if ($this->user['balance'] < $appSetting['send_msg_pay']) {
            $this->error("您的余额不足",'',[],'-2');
        }

        //主播信息//非主播则工会ID=0
        $anchor_id = $this->request->post('id', 0, 'intval');
        $anchor = Db::name("live_anchor")->where('user_id',$anchor_id)->find();
        if (!$anchor) {
            $anchor = ['guide_id' => 0];
        }
        //生成订单
        $data = [
            'user_id'       => $this->userId,
            'anchor_id'     => $anchor_id,
            'room_id'       => 0,
            'sn'            => create_sn($now,"pay_consume"),
            'type'          => 5,
            'coin'          => $appSetting['send_msg_pay'],
            'add_time'      => $now,
            'ip'            => get_client_ip(),
            'gift_id'       => 0,
            'gift_num'      => 0
        ];

        $pModel = new PayConsumeModel();
        $re = $pModel->consume($data, $anchor, $this->user['balance']);
        if ($re['ret']) {
            $reData = [
                'balance'       => $re['balance']
            ];
            $this->success("扣费成功",$reData);
        } else {
            $this->error("扣费失败，请重试");
        }
    }

    /**
     * 推荐主播列表//'1'=>'附近人','2'=>'寻找结婚对象','3'=>'找个人看电影','4'=>'约个饭，见个面','5'=>'优质女生'
     */
    public function recoms()
    {
        $lng = $this->request->param("lng");//经度//117.120391
        $lat = $this->request->param("lat");//纬度//39.094198
        $address = $this->getAddress($lat.','.$lng);
        $province = $address['province'];
        $city = $address['city'];

        $now = time();
        $isVip = 0;
        //判断当前用户是否为VIP用户
        if ($this->user['vip'] >= $now)
        {
            $isVip = 1;
        }

        $recomType = [
            ['type'=>1,'name'=>'附近人',          'brief'=>'让你轻松交往同城小伙伴',     'show' => 1],
            ['type'=>2,'name'=>'寻找结婚对象',    'brief'=>'期望一年内结婚，非诚勿扰',  'show' => 1],
            ['type'=>3,'name'=>'找个人看电影',    'brief'=>'找个人看电影',              'show' => 1],
            ['type'=>4,'name'=>'约个饭，见个面',  'brief'=>'约个饭，见个面',            'show' => $isVip],
            ['type'=>5,'name'=>'优质女生',        'brief'=>'优质女生',                   'show' => $isVip],
        ];

        $limit = 10;
        $reData = [
            'group' => $recomType,
            'data'  => []
        ];
        foreach ($recomType as $value)
        {
            $tmp = [];
            $list = Db::name('user')->alias('u')
                ->join('yz_live_anchor a', 'u.id =a.user_id')
                ->field('u.*,a.level')
                ->whereLike('u.recom_type','%"'.$value['type'].'"%')
                ->order("rand()")->limit($limit)
                ->select();
            foreach ($list as $item)
            {
                if ($item['avatar'] != '' &&  strpos($item['avatar'],'http://') === false){
                    $item['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$item['avatar'];
                }
                $age = floor( ( time() - $item['birthday'] ) / 86400 / 365 );
                $more = json_decode($item['more'],true);
                if ($value['type'] == 1)
                {
                    $item['province'] = $province;
                    $item['city'] = $city;
                }
                $tmp[] = [
                    'user_id'        => $item['id'],
                    'nickname'      => $item['user_nickname'],
                    'avatar'        => $item['avatar'],
                    'age'           => $age,
                    'job'           => $more['job'],
                    'province'      => empty($item['province'])?'':$item['province'],
                    'city'          => empty($item['city'])?'':$item['city'],
                    'vip_type'      => empty($more['vip_type']) ? 2 : $more['vip_type']
                ];
            }
            $reData['data'][] = $tmp;
        }

        //如果用户所在城市变更，将当前用户所在城市写入数据库
        if ($this->user['province'] != $province || $this->user['city'] != $city)
        {
            Db::name('user')->where('id',$this->userId)->update(['province'=>$province,'city'=>$city]);
        }

        $this->success("succ",$reData);
    }

    public function comment()
    {
        $id = $this->request->post('id', 0, 'intval');
        $page = $this->request->post('page', 1, 'intval');
        $pageSize = 10;
        $start = ($page - 1) * $pageSize;

        if ($id == 0)//不传ID，则获取当前登录用户的相册
        {
            $where = ['anchor_id'=>$this->userId];
        }
        else
        {
            $where = ['anchor_id'=>$id,'status'=>1];
        }

        $list = Db::name("live_comment")->alias('c')
            ->field('c.id,u.id user_id,u.user_nickname nickname,avatar,signature,sex,c.content,c.add_time')
            ->join('__USER__ u', 'c.user_id =u.id')
            ->where($where)
            ->order("c.add_time desc")
            ->limit($start,$pageSize)
            ->select();
        foreach ($list as $k => $value)
        {
            $url = $value['avatar'];
            if ($url != '' &&  strpos($url,'http://') === false){
                $value['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$url;
            }
            $value['add_time'] = date("Y-m-d H:i:s",$value['add_time']);
//            $count = mb_strlen($value['nickname'],'utf-8');
//            $str = mb_substr($value['nickname'],0,1,'utf-8');
//            $value['nickname1']= $str . str_repeat("*",($count - 1));
            $value['nickname'] = mb_substr($value['nickname'],0,1,'utf-8') . '*****';

            $list[$k] = $value;
        }
        $this->success("获取成功",$list);
    }

    public function recomList() {
        $type = $this->request->param("type");//推荐类型
        $next = $this->request->param("more",0,'intval');//加载更多,0否，1是
        if ($type == '1') {
            $lng            = $this->request->param("lng");//经度//117.120391
            $lat            = $this->request->param("lat");//纬度//39.094198
            $address        = $this->getAddress($lat.','.$lng);
            $province       = $address['province'];
            $city           = $address['city'];
        }
        $auDB = Db::name('user')->alias('u')
            ->join('yz_live_anchor a' , 'u.id =a.user_id')
            ->field('u.*,a.level,rand() distance')
            ->whereLike('u.recom_type' , '%"' . $type . '"%');

        $userMore = json_decode($this->user['more'],true);
        $recomShow = [];
        if ($next == 1) {//加载更多时，获取用户已查看列表的ID
            if (!empty($userMore['recom_show'])) {
                $recomShow = explode(',',$userMore['recom_show']);
            }
            $auDB->whereNotIn('u.id',$recomShow);
        }
        $list = $auDB->order("distance asc")->limit(20)->select();
        $reData = [];
        foreach ( $list as $item ) {
            $recomShow[] = $item['id'];
            if ( $item['avatar'] != '' &&  strpos( $item['avatar'] , 'http://' ) === false ) {
                $item['avatar'] = 'http://' . $_SERVER['HTTP_HOST'] . $item['avatar'];
            }
            $age = floor( ( time() - $item['birthday'] ) / 86400 / 365 );
            $more = json_decode( $item['more'] , true );
            if ( $type == 1 ) {
                $item['province'] = $province;
                $item['city'] = $city;
            }
            $dd = empty( $more['long'] ) ? rand( 50 , 5000 ) : $more['long'];
            if ( $dd < 1000 ) {
                $dd = $dd."m";
            } else {
                $dd = round($dd/1000,1).'km';
            }
            $tt = rand( 5 , 7200 );//2小时以内
            if ( $tt > 3600 ) {
                $tt = round($tt / 3600).'小时前';
            } elseif( $tt > 60 ) {
                $tt = round($tt / 60).'分钟前';
            } else {
                $tt = $tt.'秒前';
            }
            $reData[] = [
                'user_id'       => $item['id'],
                'nickname'      => $item['user_nickname'],
                'avatar'        => $item['avatar'],
                'age'           => $age,
                'job'           => $more['job'],
                'province'      => empty( $item['province'] ) ? '' : $item['province'],
                'city'          => empty( $item['city'] ) ? '' : $item['city'],
                'vip_type'      => empty( $more['vip_type'] ) ? 2 : $more['vip_type'],
                'distance'      => $dd,
                'duration'      => $tt
            ];
        }
        //更新已查看列表的ID
        $userMore['recom_show'] = implode(',',$recomShow);
        Db::name('user')->where('id',$this->userId)->update(['more'=>json_encode($userMore)]);

        $this->success('获取成功',$reData);
    }

    /**
     * 百度地图API根据经纬度获取地址
     * @param $location
     * @return mixed
     */
    private function getAddress($location)
    {
        $ak = "5ZcooHwpaRsFsmDNmm93rBF61TB7nf80";//API控制台申请得到的ak（此处ak值仅供验证参考使用）
        $sk = "tEDlKdsFZ59sebGzUYMLIQMZAYQUc54M";//应用类型为for server, 请求校验方式为sn校验方式时，系统会自动生成sk，可以在应用配置-设置中选择Security Key显示进行查看（此处sk值仅供验证参考使用）

        $uri = "/geocoder/v2/";
        $data = [
            'location'     => $location,
            'output'       => 'json',
            'ak'            => $ak
        ];
        $querystring = http_build_query($data);
        $sn = md5(urlencode($uri.'?'.$querystring.$sk));

        $url = "http://api.map.baidu.com".$uri."?".$querystring."&sn=".$sn;
        $address = json_decode($this->curlGet($url),true);
        return $address['result']['addressComponent'];
    }

    /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     */
    private function curlGet($url, $header = array( 'Content-Type: application/json; charset=utf-8' ) )
    {
        $ch = curl_init ();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $ret = curl_exec ( $ch );
        if (false == $ret) {
            $result = curl_error(  $ch);
        } else {
            $rsp = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                $result = "请求状态 ". $rsp . " " . curl_error($ch);
            } else {
                $result = $ret;
            }
        }
        curl_close ( $ch );
        return $result;
    }

    /**
     * 生成话术信息
     * @param $userId
     * @param $device
     * @param $time
     */
    private function createMsg($userId, $time)//$userId, $time
    {
        $arrTimes = [30,90];//30秒后发送第一个，再过90秒发送第二个
        //发送话术的虚拟用户数
        $num = rand(1,2);

        //读取已发送话术记录
        $sendLogUrl = CMF_ROOT . 'data/huashu_log/'.$userId;
        if(!file_exists($sendLogUrl)){
            $fdata = [
                'user_id'       => '',
                'lasttime'      => 0
            ];
        } else {
            $fdata = file_get_contents($sendLogUrl);
            $fdata = json_decode($fdata,true);
        }

        if ( ( $fdata['lasttime'] + 300 ) > $time ){//5分钟内不再发送
            return false;
        }
        $where = ['m.is_after' => 0];
        if ($fdata['user_id'] != '') {
            $where['m.user_id'] = ['<',$fdata['user_id']];
        }

        $list = Db::name('live_message')->alias('m')
            ->join('yz_user u','m.user_id=u.id')
            ->field('u.*,m.*')
            ->whereIn('type',['1','2','3'])
            ->where($where)
            ->order('m.user_id desc,m.id')
            ->select();

        $id = $i = $t = 0;//当前发送的用户ID、序号//发送消息的时间戳
        foreach ($list as $value) {
            if ($id != $value['user_id']) {
                $time = $time + $arrTimes[$i];      //当前用户第一条消息的发送时间
                $i++;                               //序号递增
                if ($i >= $num) break;             //发送消息的用户数 > 要求用户数，则退出循环
                $id = $value['user_id'];            //更新ID
                $t = $time;
            }
            //极光版本
            $data = [
                'from_id'       => 'QY_'.$id,
                'target_id'     => 'QY_'.$userId,
            ];
            switch ($value['type']){
                case 1:
                    $data['msg_type'] = 'text';
                    $data['text'] = $value['message'];
                    break;
                case 2:
                    $data['msg_type']   = 'image';
                    $data['msg_body']   = json_decode($value['jpush_media']);
                    break;
                case 3:
                    $data['msg_type']   = 'voice';
                    $data['msg_body']   = json_decode($value['jpush_media']);
                    break;
            }

            $fname = $t."_".$userId;//消息保存文件名：时间戳_用户ID
            $logFile = CMF_ROOT . 'data/huashu/'.$fname;
            $file = fopen($logFile,"a") or die("Unable to open file!");//创建文件
            fwrite($file, json_encode($data));//将内容写入文件
            fclose($file);//关闭文件

            //更新下一条的时间
            if ($value['second'] == 0) {
                $t+=rand(60,90);
            } else {
                $t+=$value['second'];
            }
        }

        $fdata = [
            'lasttime'      => $t,
            'user_id'       => $id
        ];
        $sendLog = fopen($sendLogUrl,"w");
        fwrite($sendLog, json_encode($fdata));
        fclose($sendLog);
    }
    /**
     * 生成登录后的视频信息
     * @param $userId
     * @param $time
     */
    private function createVideo($userId,$time,$anchorId)
    {
        //读取已发送话术记录
        $videoLogUrl = CMF_ROOT . 'data/video_log/'.$userId;
        if(!file_exists($videoLogUrl)) {
            $fdata = [
                'order'       => 0,
                'lasttime'      => 0
            ];
        } else {
            $fdata = file_get_contents($videoLogUrl);
            $fdata = json_decode($fdata,true);
        }
        if ( ( $fdata['lasttime'] + 300 ) > $time ){//5分钟内不再发送
            return false;
        }

        $time += rand(10,30);//10-30秒后推送视频
        $fdata['lasttime'] = $time;

        //判断用户是否有视频，有则直接推送
        $video = Db::name("user_video")->where(['user_id' => $anchorId , 'send_order' => [ '>' , 0 ] ])->order("rand()")->find();
        if (!$video) {//主播无视频，按照顺序继续推送，更新播放序号
            $video = Db::name("user_video")->where([ 'send_order' => [ '>' , $fdata['order'] ] ])->order("send_order asc")->find();
            $fdata['order'] = $video['send_order'];
        }
        //获取推送视频的用户信息
        $videoUser = Db::name('user')->where('id',$video['user_id'])->find();

        if ($videoUser['avatar'] != '' &&  strpos($videoUser['avatar'],'http://') === false){
            $videoUser['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$videoUser['avatar'];
        }
        if ($video['url'] != '' &&  strpos($video['url'],'http://') === false){
            $video['url'] = 'http://'.$_SERVER['HTTP_HOST'] .$video['url'];
        }
        $data = [
            'msg'         => $videoUser['user_nickname']."刚刚发来了视频邀请",
            'type'        => 4,
            'user_id'     => $userId,
            'anchor_id'   => $videoUser['id'],
            'url'          => $video['url'],//视频URL
            'avatar'       => $videoUser['avatar'],//头像
            'nickname'    => $videoUser['user_nickname']//昵称
        ];

        //消息保存文件名：时间戳_用户ID
        $fname = $time."_".$userId;
        //var_dump($fname);
        //将消息内容写入文件
        //创建文件
        $logFile = CMF_ROOT . 'data/video/'.$fname;
        $file = fopen($logFile,"a") or die("Unable to open file!");
        //将内容写入文件
        fwrite($file, json_encode($data));
        //关闭文件
        fclose($file);

        //极光版本
        $pushData = [
            'from_id'       => 'QY_'.$data['anchor_id'],
            'target_id'     => 'QY_'.$data['user_id'],
            'msg_type'      => 'custom',
            'text'          => "[视频邀请]"
        ];
        $plogFile = CMF_ROOT . 'data/huashu/'.$fname;
        $pfile = fopen($plogFile,"a") or die("Unable to open file!");//创建文件
        fwrite($pfile, json_encode($pushData));//将内容写入文件
        fclose($pfile);//关闭文件

        $videoLog = fopen($videoLogUrl,"w");
        fwrite($videoLog, json_encode($fdata));
        fclose($videoLog);
    }
    /**
     * 极光推送 - 随机获取一个推荐主播
     */
    private function getAnchor($anchorId)
    {
        //判断主播是否有视频
        $check = Db::name("live_anchor")->where('user_id',$anchorId)->find();
        if ($check['video_state'] == 1){//主播有视频
            $where = "where a.user_id=".$anchorId;
        } else {//主播无视频
            $where = "where a.recommend = 1 and a.video_state = 1 and r.id is null and v.id is null order by rand() asc ";
        }

        $sql ="select "
            . "u.id uid,u.user_nickname nickname,u.avatar,u.sex,u.signature,u.more,a.photo,a.level,a.single_coin coin,a.video_url "
            . "from yz_live_anchor as a "
            . "left join yz_live_room as r on a.user_id = r.user_id and r.live_end = 0 "
            . "left join yz_live_video as v on a.user_id = v.anchor_id and v.end_time = 0 "
            . "left join yz_user as u on u.id = a.user_id "
            . $where;
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

        if ($info['avatar'] != '' &&  strpos($info['avatar'],'http://') === false){
            $info['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$info['avatar'];
        }
        if ($info['photo'] != '' &&  strpos($info['photo'],'http://') === false){
            $info['photo'] = 'http://'.$_SERVER['HTTP_HOST'] .$info['photo'];
        }
        if ($info['video_url'] != '' &&  strpos($info['video_url'],'http://') === false){
            $info['video_url'] = 'http://'.$_SERVER['HTTP_HOST'] .$info['video_url'];
        }
        return [
            'ret'   => true,
            'msg'   => "获取成功",
            'data'  => $info
        ];
    }
}