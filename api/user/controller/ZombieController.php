<?php
// +----------------------------------------------------------------------
// | 虎虎科技
// +----------------------------------------------------------------------
// | 抓取僵尸主播信息，并入库
// +----------------------------------------------------------------------
// | Author: 李雪
// +----------------------------------------------------------------------
namespace api\user\controller;

use think\Db;
use cmf\controller\RestBaseController;
use think\cache\driver\Redis;
use think\Cache;

class ZombieController extends RestBaseController
{
    private $nextPage = 0;
    private $users = [];
    private $anchors = [];
    private $rooms = [];

    /*脚本-清理僵尸直播间，更新最新的直播间
check="true"
url_cmd=`curl -s http://yz.squareorange.cn/api/user/zombie/check/`
while [ $check == "true" ]
do
    if [ $url_cmd == "success" ]
    then
        check="false"
    else
        id=`echo $url_cmd | cut -d \, -f 1`
        status=`echo $url_cmd | cut -d \, -f 2`
        echo "${id}:${status}"
        url_cmd=`curl -s http://yz.squareorange.cn/api/user/zombie/check?id=${id}`
    fi
done
url_cmd=`curl -s http://yz.squareorange.cn/api/user/zombie/reg/?p=9158`
echo "=========="
echo "$url_cmd"
     */
    
    /**
     * 判断僵尸主播直播状态
     */
    public function check()
    {
        $id = $this->request->param('id', 0 , "intval");//默认起始ID：0
        $room = Db::name('live_room')->where(['live_url'=>['<>','']])->where('live_state',1)->where(['id'=>['>',$id]])->order('id asc')->find();
        if (!$room)
        {
            exit("end");
        }

        $array = get_headers($room['live_url'],1);
        if(preg_match('/200/',$array[0])){
            echo $room['id'].",直播中...";
        }else{
            Db::name('live_room')->where('id',$room['id'])->update(['live_end'=>time(),'live_state'=>2]);
            echo $room['id'].",直播结束";
        }
        exit();
    }

    /**
     *  僵尸用户注册
     */
    public function reg()
    {
        $pt = $this->request->param('p');
        $page = $this->request->param('page', 1 , "intval");//默认页数：1

        switch ($pt)
        {
            case "9158":
                $re = $this->get9158($pt,$page);
                break;
            default:
                $this->error("参数错误");
        }

        if ($re)//数据成功转换
        {
            $userQuery = Db::name("user");
            $num = $add = $edit = 0;

            foreach ($this->users as $val)
            {
                $num++;
                $findUser = $userQuery->where('zombie_from',$pt)->where('zombie_id',$val['zombie_id'])->find();
                if (empty($findUser)) {//用户不存在，注册新用户，并添加主播、直播间信息
                    $userId = $userQuery->insertGetId($val);
                    $val['id'] = $userId;
                    //创建用户Token
                    $token = $this->getToken($userId, 'pc');
                    $this->userRedis($token,$userId,$val);

                    //添加主播信息
                    $anchor = $this->anchors[$val['zombie_id']];
                    $anchor['user_id'] = $userId;
                    Db::name('live_anchor')->insert($anchor);

                    //添加直播间信息
                    $room = $this->rooms[$val['zombie_id']];
                    $room['user_id'] = $userId;
                    $room['live_code']  = '31697_'.$userId.'_'.$room['live_start'];
                    Db::name('live_room')->insert($room);

                    $this->roomRedis($userId,$val,$anchor,$room);
                    $add++;
                }
                else//用户已存在，更新直播间信息
                {
                    $userId = $findUser['id'];

                    $room = Db::name('live_room')->where('user_id',$userId)->find();
                    if ($room['live_state'] != 1)//已关闭的直播间，更新直播间信息
                    {
                        if (!$room){//没有房间信息的，新增
                            //添加主播信息
                            $anchor = $this->anchors[$val['zombie_id']];
                            $anchor['user_id'] = $userId;
                            Db::name('live_anchor')->insert($anchor);

                            //添加直播间信息
                            $room = $this->rooms[$val['zombie_id']];
                            $room['user_id'] = $userId;
                            $room['live_code']  = '31697_'.$userId.'_'.$room['live_start'];
                            Db::name('live_room')->insert($room);
                        } else {
                            $room = array_merge($room,$this->rooms[$val['zombie_id']]);
                            $room['live_code']  = '31697_'.$userId.'_'.$room['live_start'];
                            Db::name('live_room')->update($room);
                        }
                      
                        $anchor = Db::name('live_anchor')->where('user_id',$userId)->find();
                        $this->roomRedis($userId,$val,$anchor,$room);
                        $edit ++;
                    }
                }
            }
            exit($page."数据添加成功,共".$num."条数据，新增".$add."条记录，修改".$edit."条记录");
            //$this->success("数据添加成功,共".$num."条数据，新增".$add."条记录，修改".$edit."条记录");
        }
        else
        {
            exit('end');
            //$this->error("数据添加失败");
        }
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
        $currentTime = time();
        $expireTime = $currentTime + 24 * 3600 * 180;
        $token = md5(uniqid()) . md5(uniqid());
        $result = Db::name("user_token")->insert([
            'token' => $token,
            'user_id' => $userId,
            'expire_time' => $expireTime,
            'create_time' => $currentTime,
            'device_type' => $device
        ]);
        return $token;
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
            "signature"=> $user['signature'],
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
        Cache::store('redis')->set($token,json_encode($redisData));
    }

    /**
     * 将房间数据写入redis
     * @param $userId
     * @param $user
     * @param $anchor
     * @param $room
     */
    private function roomRedis($userId,$user,$anchor,$room)
    {
        //房间数据写入redis
        $redisData = [
            'id'                => $userId,
            'user_nicename'   => $user['user_nickname'],
            'avatar'           => $user['avatar'],
            'sex'              => $user['sex'],
            'signature'        => $user['signature'],
            'experience'       => '',
            'consumption'      => '',
            'votestotal'       => $anchor['gift_total'],
            'province'         => '',
            'city'             => '',
            'isrecommend'     => 0,
            'showid'           => $room['live_code'],
            'starttime'        => $room['live_create'],
            'title'            => $room['live_title']
        ];
        $redis =new Redis();   //实例化
        $redis->handler()->hset('livelist',$userId,json_encode($redisData));
    }

    /**
     * 获取9158平台主播信息
     */
    private function get9158($pt,$page)
    {
        $url = "http://live.9158.com/Room/GetNewRoomOnline/?page=" . $page;

        $data = $this->curlGet($url);

        $data = json_decode($data, 1);

        $reDara = false;

        if ($data['code'] == "100")
        {
            $data = $data['data'];

            if ($page < $data['totalPage'])//判断是否存在下一页
            {
                $this->nextPage = $page + 1;
            }
            $now = time();
            foreach ($data['list'] as $key => $value)//处理数据
            {
                $this->users[] = [
                    'sex'                => $value['sex'] == 0 ? 2 : $value['sex'],
                    'avatar'             => $value['photo'],
                    'user_nickname'     => $value['nickname'],
                    'signature'        => "这个人很懒，什么也没有留下......",
                    'create_time'       => $now,
                    'create_ip'         => "127.0.0.1",
                    'user_status'       => 1,
                    'user_type'         => 2,
                    'last_login_time'   => $now,
                    'last_login_ip'     => "127.0.0.1",
                    'is_zombie'         => 1,
                    'zombie_from'       => $pt,
                    'zombie_id'         => $value['useridx']
                ];
                $recom = rand(0,1);
                $this->anchors[$value['useridx']] = [
                    'photo'             => $value['photo'],
                    'add_time'          => $now,
                    'audit_time'       => $now,
                    'level'             => ($value['anchorLevel'] % 4) + 1,
                    'status'            => 1,
                    'single_coin'       => 10,
                    'ratio'             => 100,
                    'gift_total'        => 0,
                    'recommend'         => $recom,
                    'recom_time'        => ($recom == 1 ? $now : 0),
                ];
                $this->rooms[$value['useridx']] = [
                    'live_type'         => 1,
                    'live_title'        => $value['nickname'],
                    'live_create'       => $now,
                    'live_start'        => $now,
                    'live_end'          => 0,
                    'live_state'        => 1,
                    'live_url'          => $value['flv'],
                    'has_zombie'        => 0
                ];
            }
            $reDara = true;
        }
        return $reDara;
    }

    /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     */
    private function curlGet($url)
    {
        $ch = curl_init ();
        curl_setopt( $ch, CURLOPT_URL, $url );
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
     * 添加僵尸粉观众
     */
    public function join()
    {
        //随机获取一个没有僵尸观众的直播中的直播间
        $room = Db::name('live_room')->where('live_state',1)->where('has_zombie',0)->order('live_create')->find();
        if (!$room)//无直播间需要僵尸观众
        {
            exit("end");
        }

        $nums = rand(10,20);
        //随机获取10-20个僵尸用户
        $list = Db::name('user')->whereOr('is_zombie','1')->whereOr('is_virtual','1')->order('rand()')->limit($nums)->field('id')->select();
        if (!$list || count($list) == 0)
        {
            exit("end");
        }

        $insert = [];
        $now = time();
        foreach ($list as $value)
        {
            //判断是否已经加入主播直播间
            $check = Db::name("live_user")->where('anchor_id',$room['user_id'])->where('user_id',$value['id'])->find();
            if ($check)//已存在，则修改状态
            {
                $check["join_time"] = $now;
                $check["exit_time"] = 0;
                $check["times"]      = $check["times"] + 1;
                $check["is_zombie"] = 1;
                Db::name("live_user")->update($check);
            }
            else
            {
                $insert[] = [
                    'anchor_id'     => $room['user_id'],
                    'user_id'       => $value['id'],
                    'join_time'     => $now,
                    'exit_time'     => 0,
                    'times'         => 1,
                    'is_zombie'     => 1,
                ];
            }
        }
        if (count($insert) == 1)
        {
            Db::name("live_user")->insert($insert[0]);
        }
        if (count($insert) > 1)
        {
            Db::name("live_user")->insertAll($insert);
        }
        $userNum = count($list);
        Db::name('live_room')->where('id',$room['id'])->update([
            'has_zombie'    => 1,
            'audience'      => $room['audience'] + $userNum,
            'aud_max'       => $room['aud_max'] + $userNum,
            'aud_total'     => $room['aud_total'] + $userNum,
        ]);
        exit("用户ID:".$room['user_id']."的房间，增加".count($list)."名观众");
    }
  
    /**
     * 添加私播直播间
     */
    public function addLive()
    {
        $list = Db::name('user')->where('is_virtual',1)->order('rand()')->limit(10)->select();
        $data = $ids = [];
        $now = time();
        foreach ($list as $value)
        {
            $ids[] = $value['id'];
            $data[] = [
                'user_id'       => $value['id'],
                'live_type'     => 6,
                'live_title'    => $value['user_nickname'],
                'live_create'   => $now,
                'live_start'    => $now,
                'live_state'    => 1
            ];
        }
        $db = Db::name('live_room');
        $roomHas = $db->where('live_type',6)->whereIn('user_id',$ids)->column('user_id');

        foreach ($data as $k => $v)
        {
            if (in_array($v['user_id'],$roomHas)){
                unset($data[$k]);
            }
        }
        $db->whereIn('user_id',$ids)->update(['live_start'=>$now,'live_state'=>1]);
        if (count($data) == 1)
        {
            $db->insert($data[0]);
        } elseif(count($data) > 1) {
            $db->insertAll($data);
        }
        $this->success("add-".count($data).",edit-".count($roomHas));
    }
}