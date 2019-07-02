<?php
// +----------------------------------------------------------------------
// | 虎虎科技
// +----------------------------------------------------------------------
// | Author: 李雪
// +----------------------------------------------------------------------
namespace api\user\controller;

use think\Db;
use think\Exception;
use think\Validate;
use cmf\controller\RestBaseController;
use think\Cache;

class PublicController extends RestBaseController
{
    /**
     *  用户注册
     */
    public function register()
    {
        $validate = new Validate([
            'mobile' => 'require',
            'password' => 'require|min:6|max:20',
            'code' => 'require'
        ]);

        $validate->message([
            'mobile.require' => '请输入手机号码!',
            'password.require' => '请输入您的密码!',
            'password.max' => '密码不能超过20个字符',
            'password.min' => '密码不能小于6个字符',
            'code.require' => '请输入验证码!'
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $user = [];

        $userQuery = Db::name("user");

        if (preg_match('/(^(13\d|15[^4\D]|17[13678]|18\d)\d{8}|170[^346\D]\d{7})$/', $data['mobile'])) {
            $user['mobile'] = $data['mobile'];
            $userQuery = $userQuery->where('mobile', $data['mobile']);
        } else {
            $this->error("请输入正确的手机格式!");
        }
        if (!preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,20}$/', $data['password'])) {
            $this->error("请输入由数字、字母组合的密码!");
        }

        $errMsg = cmf_check_verification_code($data['mobile'], $data['code']);
        if (!empty($errMsg)) {
            $this->error($errMsg);
        }

        $findUserCount = $userQuery->count();

        if ($findUserCount > 0) {
            $this->error("手机号码已注册!");
        }

        $user['sex']             = $data['sex'];
        $user['create_time']    = time();
        $user['create_ip']      = get_client_ip(0, true);
        $user['user_status']    = 1;
        $user['user_type']      = 2;
        $user['user_pass']      = cmf_password($data['password']);
        $user['user_nickname']  = $this->randNickName();
        $user['last_login_time']= $user['create_time'];
        $user['last_login_ip']  = $user['create_ip'];

        Db::startTrans();
        try
        {
            $userId = $userQuery->insertGetId($user);

            if (empty($userId)) {
                $this->error("注册失败,请重试!");
            }

            //发放注册奖励
            $rePrice = $this->regPrice($userId);

            //记录下载数据
            $downId = $this->request->post('down_id', 0, 'intval');
            $reDown = $this->downInfo($userId, $user, $downId, $data['device_type']);

            //获取用户Token
            $token = $this->getToken($userId, $data['device_type']);

            //将用户TOMEN写入user->more
            $more['token'] = $token;
            Db::name('user')->where('id',$userId)->update(['more'=>json_encode($more)]);

            //发送话术
            //$this->createMsg($userId,$data['device_type'],$user['create_time']);

            //注册成功后直接跳转登录页，因此注册是不需要写入redis
//            $reRedis = $this->userRedis($token,$userId,$user);
//            var_dump($reRedis);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
//            var_dump($e);
            $this->error("注册失败!");
        }
        $this->success("注册成功!", ['token' => $token]);
    }

    /**
     * 用户登录
     * TODO 增加最后登录信息记录,如 ip
     * @throws \think\Exception
     */
    public function login()
    {
        $validate = new Validate([
            'mobile' => 'require',
            'password' => 'require'
        ]);
        $validate->message([
            'mobile.require' => '请输入手机号码!',
            'password.require' => '请输入您的密码!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $userQuery = Db::name("user");
        if (preg_match('/(^(13\d|15[^4\D]|17[13678]|18\d)\d{8}|170[^346\D]\d{7})$/', $data['mobile'])) {
            $userQuery = $userQuery->where('mobile', $data['mobile']);
        } else {
            $this->error("请输入正确的手机格式!");
        }

        $findUser = $userQuery->find();

        if (empty($findUser)) {
            $this->error("用户不存在!");
        } else {

            switch ($findUser['user_status']) {
                case 0:
                    $this->error('您的账号已被锁定!');
                case 2:
                    $this->error('账户还没有验证成功!');
            }

            if (!cmf_compare_password($data['password'], $findUser['user_pass'])) {
                $this->error("密码不正确!");
            }
        }

        //获取用户Token
        $token = $this->getToken($findUser['id'], $data['device_type']);

        $findUser['last_login_ip']  = get_client_ip(0, true);
        $findUser['last_login_time']= time();

        $this->userRedis($token,$findUser['id'],$findUser);

        if ($findUser['avatar'] != '' &&  strpos($findUser['avatar'],'http://') === false){
            $findUser['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$findUser['avatar'];
        }
        $more = json_decode($findUser['more'],true);
        if (isset($more['token']))
        {
            $token_tmp = $more['token'];
        } else {
            $token_tmp = $token;
            //将用户TOMEN写入user->more
            $more['token'] = $token_tmp;
            $findUser['more']= json_encode($more);
        }
        //修改登录时间，more-token(如果有必要)
        Db::name('user')->update($findUser);
        $url = "http://appstore.le8le8.cn/apptab/list.html?"
            . "otheruid=" . $findUser['id']//."_".$data['device_type']
            . "&othertoken=" . $token_tmp
            . "&nickname=" . urlencode($findUser['user_nickname'])
            . "&gender=" . $findUser['sex']
            . "&phonetype=" . "mobile"
            . "&imsi=" . "1234"
            . "&avatarurl=" . urlencode($findUser['avatar']);

        if ($findUser['vip'] < $findUser['last_login_time']) {//非VIP用户，发送话术
            $this->createMsg($findUser['id'],$data['device_type'],$findUser['last_login_time']);
            $this->createVideo($findUser['id'],$findUser['last_login_time']);
        }

        $this->success("登录成功!", ['token' => $token,'third_live_url' => $url]);
    }

    /**
     * 第三方用户登录
     */
    public function thirdPartyLogin()
    {
        $validate = new Validate([
            'type' => 'require',
            'openid' => 'require',
            'sex' => 'require',
            'avatar' => 'require',
            'nickname' => 'require',
        ]);

        $validate->message([
            'type.require' => '请选择第三方渠道!',
            'openid.require' => '授权信息获取失败!',
            'sex.require' => '请选择性别',
            'avatar.require' => '头像获取失败!',
            'nickname.require' => '昵称获取失败!'
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $userQuery = Db::name("user");
        $user = [];

        if ($data['type'] == 'wx') {
            $user['wx_openid'] = $data['openid'];
            $userQuery = $userQuery->where('wx_openid', $data['openid']);
        } elseif ($data['type'] == 'qq') {
            $user['qq_openid'] = $data['openid'];
            $userQuery = $userQuery->where('qq_openid', $data['openid']);
        } else {
            $this->error("您的操作有误");
        }

        $findUser = $userQuery->find();

        if (empty($findUser)) {//用户不存在，注册新用户

            $user['sex'] = $data['sex'];
            $user['avatar'] = urldecode($data['avatar']);
            $user['user_nickname'] = urldecode($data['nickname']);

            $user['create_time'] = time();
            $user['create_ip']      = get_client_ip(0, true);
            $user['user_status'] = 1;
            $user['user_type'] = 2;

            $user['last_login_time']= $user['create_time'];
            $user['last_login_ip']  = $user['create_ip'];

            $userId = $userQuery->insertGetId($user);
            $user['id'] = $userId;

            //发放注册奖励
            $rePrice = $this->regPrice($userId);

            //记录下载数据
            $downId = $this->request->post('down_id', 0, 'intval');
            $this->downInfo($userId, $user, $downId, $data['device_type']);

        } else {
            $user = $findUser;
            switch ($findUser['user_status']) {
                case 0:
                    $this->error('您的账号已被锁定!');
                case 2:
                    $this->error('账户还没有验证成功!');
            }
            $userId = $findUser['id'];

            $user['last_login_time']= time();
            $user['last_login_ip']  = get_client_ip(0, true);
        }
      
        if ($user['vip'] < $user['last_login_time']) {//非VIP用户，发送话术
            $this->createMsg($userId,$data['device_type'],$user['last_login_time']);
            $this->createVideo($userId,$user['last_login_time']);
        }

        //获取用户Token
        $token = $this->getToken($userId, $data['device_type']);

        $this->userRedis($token,$userId,$user);

        if (isset($user['more']) && $user['more']!='')
            $more = json_decode($user['more'],true);
        else
            $more = [];
        if (isset($more['token']))
        {
            $token_tmp = $more['token'];
        } else {
            $token_tmp = $token;
            //将用户TOMEN写入user->more
            $more['token'] = $token_tmp;
            $user['more'] = json_encode($more);
        }
        //第一次登录的，修改more-token，再次登录的修改时间
        Db::name('user')->update($user);
        if ($findUser['avatar'] != '' &&  strpos($findUser['avatar'],'http://') === false){
            $findUser['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$findUser['avatar'];
        }
        $url = "http://appstore.le8le8.cn/apptab/list.html?"
            . "otheruid=" . $findUser['id']//."_".$data['device_type']
            . "&othertoken=" . $token_tmp
            . "&nickname=" . urlencode($findUser['user_nickname'])
            . "&gender=" . $findUser['sex']
            . "&phonetype=" . "mobile"
            . "&imsi=" . "1234"
            . "&avatarurl=" . urlencode($findUser['avatar']);

        $this->success("登录成功!", ['token' => $token,'third_live_url' => $url]);
    }

    /**
     * 将用户信息写入redis
     * @param $token
     * @param $userId
     * @param $user
     */
    private function userRedis($token,$userId,$user){
        $redisData = [
            "id"    => $userId,
            "user_nicename"=> $user['user_nickname'],
            "avatar"=> isset($user['avatar'])?$user['avatar']:'',
            "sex"=> $user['sex'],
            "signature"=> isset($user['signature']) ? $user['signature'] : "这个人很赖，什么也没有留下……",
            "experience"=> "",                              // 测试数据
            "consumption"=> "0",                           // 测试数据
            "votestotal"=> "0",                            // 测试数据
            "province"=> "",                                // 测试数据
            "city"=> "",                                    // 测试数据
            "isrecommend"=> "",                             // 测试数据
            "coin"=> "0",                                    // 测试数据
            "votes"=> "0",                                   // 测试数据
            "userType"=> "",                                // 测试数据
            "sign"=> ""                                     // 测试数据
        ];

        return Cache::store('redis')->set($token,json_encode($redisData));
    }

    /**
     * 获取用户Token
     * @param $userId   用户ID
     * @param $device   设备标识
     * @return string   用户Token
     * @throws \think\Exception
     */
    private function getToken($userId, $device)
    {
        $allowedDeviceTypes = ['mobile', 'android', 'iphone', 'ipad', 'web', 'pc', 'mac'];

        if (empty($device) || !in_array($device, $allowedDeviceTypes)) {
            $this->error("请求错误,未知设备!");
        }

        $userTokenQuery = Db::name("user_token")
            ->where('user_id', $userId)
            ->where('device_type', $device);
        $findUserToken = $userTokenQuery->find();
        $currentTime = time();
        $expireTime = $currentTime + 24 * 3600 * 180;//180天有效期
        $token = md5(uniqid()) . md5(uniqid());
        if (empty($findUserToken)) {
            $result = $userTokenQuery->insert([
                'token' => $token,
                'user_id' => $userId,
                'expire_time' => $expireTime,
                'create_time' => $currentTime,
                'device_type' => $device
            ]);
        } else {
            $result = $userTokenQuery
                ->where('user_id', $userId)
                ->where('device_type', $device)
                ->update([
                    'token' => $token,
                    'expire_time' => $expireTime,
                    'create_time' => $currentTime
                ]);
            Cache::store('redis')->rm($findUserToken['token']);
        }

        if (empty($result)) {
            $this->error("Token获取失败!");
        }
        return $token;
    }

    /**
     * 记录下载注册数据
     * @param $user
     * @param $downId
     */
    private function downInfo($userId,$user,$downId,$device)
    {
        switch ($device)
        {
            case 'iphone':
            case 'ipad':
            case 'mac':
            case 'ios':
                $device = "ios";
                break;
            case 'mobile':
            case 'android':
            case 'pc':
                $device = "android";
                break;
            default:
                $this->error("参数错误");
        }
        $db = Db::name('down_log');
        $down = $db->where('id',$downId)->where('is_reg',0)->find();
        if ($down)//有未注册记录的，记录注册信息
        {
            $re = $db->where('id',$downId)->update(['is_reg'=>1,'reg_time'=>$user['create_time'],'user_id'=>$userId]);
            return $re;
        }
        return true;
    }

    /**
     * 随机生成2-6位昵称
     * @return string
     */
    private function randNickName()
    {
        $num = rand(2,6);
        $b = '';
        for ($i = 0; $i < $num; $i++) {
            // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
            $a = chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0));
            // 转码
            $b .= iconv('GB2312', 'UTF-8', $a);
        }
        return $b;
    }

    /**
     * 发放注册奖励
     */
    private function regPrice($userId)
    {
        //=============== 确认规则后，待完善 ===============
        return true;
    }

    /**
     * 生成注册登录后的话术信息
     * @param $userId
     * @param $device
     * @param $time
     */
    public function createMsg($userId, $device, $time)//$userId, $device, $time
    {
        $arrTimes = [60, 90,90,120,120, 90,90,120,120, 90,90,120,120, 90,90,120,120, 90,90,120,120];//30秒后发送第一个，再过60秒发送第二个
        //发送话术的虚拟用户数
        if ($device == 'iphone') {
            $num = rand(3,5);
        } else {
            $num = rand(8,10);
        }

        //读取已发送话术记录
        $sendLogUrl = CMF_ROOT . 'data/huashu_log/'.$userId;
        if(!file_exists($sendLogUrl)) {//没有发送记录，从最后一条开始发送
            $fdata = [
                'user_id'       => '',
                'lasttime'  => 0
            ];
        } else {
            $fdata = file_get_contents($sendLogUrl);
            $fdata = json_decode($fdata,true);
        }

        if ($fdata['lasttime'] > $time) {//最后一条尚未发送，则不再增加记录
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
                $id = $value['user_id'];           //更新当前消息用户ID
                $t = $time;
            }
            //发送消息的data
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
            $logFile = CMF_ROOT . 'data/huashu/'.$fname;//将消息内容写入文件
            $file = fopen($logFile,"a") or die("Unable to open file!");//创建文件
            fwrite($file, json_encode($data)); //将内容写入文件
            fclose($file);//关闭文件

            //更新当前用户下一条的时间
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
    private function createVideo($userId,$time)
    {
        //读取已发送话术记录
        $videoLogUrl = CMF_ROOT . 'data/video_log/'.$userId;
        if(!file_exists($videoLogUrl)){
            $fdata = [
                'order'         =>0,
                'lasttime'      => 0
            ];
        } else {
            $fdata = file_get_contents($videoLogUrl);
            $fdata = json_decode($fdata,true);
        }

        if ( ( $fdata['lasttime'] + 300 ) > $time ){//5分钟内不再发送
            return false;
        }

        $time += rand(60,120);
        $info = Db::name("user_video")->alias("v")
            ->field("v.*,u.user_nickname nickname,u.avatar")
            ->join("yz_user u","u.id=v.user_id")
            ->where(['send_order'=>['>',$fdata['order']]])
            ->order('v.send_order asc')
            ->find();

        if ($info['avatar'] != '' &&  strpos($info['avatar'],'http://') === false){
            $info['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$info['avatar'];
        }
        if ($info['url'] != '' &&  strpos($info['url'],'http://') === false){
            $info['url'] = 'http://'.$_SERVER['HTTP_HOST'] .$info['url'];
        }

        //视频邀请写入文件
        $data = [
            'msg'         => $info['nickname']."刚刚发来了视频邀请",
            'type'        => 4,
            'user_id'     => $userId,
            'anchor_id'   => $info['user_id'],
            'url'          => $info['url'],//视频URL
            'avatar'       => $info['avatar'],//头像
            'nickname'    => $info['nickname']//昵称
        ];

        //消息保存文件名：时间戳_用户ID
        $fname = $time."_".$userId;
        //将消息内容写入文件
        //创建文件
        $logFile = CMF_ROOT . 'data/video/'.$fname;
        $file = fopen($logFile,"a") or die("Unable to open file!");
        //将内容写入文件
        fwrite($file, json_encode($data));
        //关闭文件
        fclose($file);

        //视频邀请消息写入文件
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

        $fdata = [
            'order'         => $info['send_order'],
            'lasttime'      => $time
        ];
        $videoLog = fopen($videoLogUrl,"w");
        fwrite($videoLog, json_encode($fdata));
        fclose($videoLog);
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
    
    /**
     * 用户退出
     * @throws \think\Exception
     */
    public function logout()
    {
        $userId = $this->getUserId();
        Db::name('user_token')->where([
            'token' => $this->token,
            'user_id' => $userId,
            'device_type' => $this->deviceType
        ])->update(['token' => '']);

        //删除极光用户
        $jpushName =  'QY_'.$userId;
        $result = hook_one("user_jpush_del", $jpushName);

        $this->success("退出成功!");
    }

    /**
     * 用户密码重置
     * @throws \think\Exception
     */
    public function passwordReset()
    {
        $validate = new Validate([
            'mobile' => 'require',
            'password' => 'require|min:6|max:20',
            'code' => 'require'
        ]);

        $validate->message([
            'mobile.require' => '请输入手机号码!',
            'password.require' => '请输入您的密码!',
            'password.max' => '密码不能超过20个字符',
            'password.min' => '密码不能小于6个字符',
            'code.require' => '请输入验证码!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $userWhere = [];
        if (preg_match('/(^(13\d|15[^4\D]|17[13678]|18\d)\d{8}|170[^346\D]\d{7})$/', $data['mobile'])) {
            $userWhere['mobile'] = $data['mobile'];
        } else {
            $this->error("请输入正确的手机格式!");
        }
        if (!preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,20}$/', $data['password'])) {
            $this->error("请输入由数字、字母组合的密码!");
        }

        $errMsg = cmf_check_verification_code($data['mobile'], $data['code']);
        if (!empty($errMsg)) {
            $this->error($errMsg);
        }

        $userPass = cmf_password($data['password']);
        Db::name("user")->where($userWhere)->update(['user_pass' => $userPass]);

        $this->success("密码重置成功,请使用新密码登录!");

    }

    /**
     * 根据用户ID获取头像、昵称等信息
     */
    public function getNameById()
    {
        $id = $this->request->get('id');
        $ids = explode(',',$id);

        $list = Db::name("user")->field('id user_id,user_nickname nickname,avatar,signature,vip,sex,more')->whereIn('id',$ids)->select();
        if (count($list) == 0)
        {
            $this->error("暂无用户数据");
        }

        $reData = [];
        foreach ($list as $value)
        {
            $more = json_decode($value['more'],true);
            $tmp = [
                'user_id'   => $value['user_id'],
                'nickname'  => $value['nickname'],
                'signature' => $value['signature'],
                'sex'        => $value['sex'],
                'vip_type'  => empty($more['vip_type']) ? 2 : $more['vip_type'],
            ];
            if ($value['avatar'] != '' &&  strpos($value['avatar'],'http://') === false){
                $tmp['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$value['avatar'];
            } else {
                $tmp['avatar'] = $value['avatar'];
            }
            if ($value['vip'] >= time())
            {
                $tmp['vip_time'] = $value['vip'];
                $tmp['vip'] = 1;
            } else {
                $tmp['vip_time'] = 0;
                $tmp['vip'] = 0;
            }

            $reData[] = $tmp;
        }
        $this->success("获取成功",$reData);
    }
    /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     */
    private function curlPost($url,$postFields)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);  //该curl_setopt可以向header写键值对
        curl_setopt($ch, CURLOPT_HEADER, false); // 不返回头信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
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
}