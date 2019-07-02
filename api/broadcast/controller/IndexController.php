<?php
// +----------------------------------------------------------------------
// | Author: Lixue
// +----------------------------------------------------------------------
namespace api\broadcast\controller;

use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;
use api\broadcast\service\PayConsumeModel;

class IndexController extends RestBaseController
{
    /**
     * api
     */
    public function index()
    {
        $this->success("直播信息反馈接收");
    }

    /**
     * 接收流推送信息
     */
    public function message()
    {
        $t = $this->request->header('t');
        $sign = $this->request->header('sign');
        $event_type = $this->request->header('event_type');
        $stream_id = $this->request->header('stream_id');
        $channel_id = $this->request->header('channel_id');
    }

    /**
     * 接收鉴黄信息
     */
    public function yellow()
    {

    }

    /**
     * 获取热门关键词
     */
    public function hotKyewords()
    {
        $appSetting = cmf_get_option('app_settings');
        $list = explode(',',$appSetting['hot_keyword']);
        $this->success("success",$list);
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
     * 直播间列表
     */
    public function rooms()
    {
        $type = $this->request->post("type");
        $where = ['r.live_state' => '1'];//只获取直播中的
        $order = "r.id desc";
        switch ($type){
            case "hot": //热门主播
                $order = 'r.live_tag desc,r.audience desc';//按照当前观众数量但需排列
                break;
            case "new": //最新开播
                $order = 'r.live_tag desc,r.live_start desc';
                break;
            case "recom": //推荐主播
                $where['a.recommend'] = 1;
                $order = 'r.live_tag desc,a.recom_time desc';
                break;
            case "priv": //私播
                $where['r.live_type'] = 6;
                $where['a.video_recom'] = 1;
                break;
            case "sel"://搜索
                $keyword = $this->request->post('keyword');
                if ($keyword == '')
                {
                    $this->error("请填写搜索关键词");
                }
                $where['u.user_nickname|r.live_title|u.signature']    = ['like', "%$keyword%"];
                $order = 'r.live_tag desc,a.recom_time desc';
                break;
            default:
                $this->error('参数错误');

        }

        $page = $this->request->post('page', 1, 'intval');
        $pageSize = 10;
        $start = ( $page - 1 ) * $pageSize;

        $list = Db::name('live_room')->alias("r")
            ->field('r.*,a.single_coin,a.gift_total,a.level,u.user_nickname,u.sex,u.avatar,u.signature,u.birthday,u.more,u.city,u.province')
            ->join('__USER__ u', 'r.user_id =u.id')
            ->join('yz_live_anchor a', 'r.user_id =a.user_id')
            ->where($where)->order($order)->limit($start,$pageSize)->select();
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
                'signature'     => $value['signature'],
                'level'         => $value['level'],
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
     * 获取礼物列表
     */
    public function giftList()
    {
        $page = $this->request->get('page', 1, 'intval');
        $pageSize = 10;//每次加载10条记录
        $start = ( $page - 1 ) * $pageSize;
        $list = Db::name("gift")
            ->field("id,name,concat('http://".$_SERVER['HTTP_HOST'] ."',pic) pic,coin")
            ->where(['status' => 1])
            ->order("mark,id")
            ->limit($start,$pageSize)
            ->select();
//
        $this->success('获取成功',$list);
    }

    /**
     * 即构-流创建回调接口--暂时不需要
     */
    public function zegoOpen()
    {
        exit("1");
    }
    /**
     * 即构-流关闭回调接口
     */
    public function zegoClose()
    {
        $appId = "84041350";
        $appKey = "0x9e,0x67,0xac,0x3f,0x4a,0x3a,0x0f,0xca,0xef,0xad,0x88,0xb5,0x98,0x8c,0x2a,0xbd,0x40,0xd5,0x9f,0xe7,0xf0,0x26,0x0d,0x75,0x5c,0x7b,0x26,0x8c,0x12,0x09,0x0f,0x95";
        $secret = "4cc6e93bd10e0181ceb7a582ed2cb592";//客户提供secret

        $time = $this->request->post("timestamp");
        $nonce = $this->request->post("nonce");
        $sign = $this->request->post("signature");

        $tmpArr = array($secret, $time, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $sign ) {
            $str = json_encode($_POST);
            //关闭相关用户的视频通话
            $room = $this->request->post("channel_id");
            //判断房间是否打开
            $video = Db::name("live_video")->where('room',$room)->where('end_time',0)->find();
            if($video) {//有未关闭的数据，进行结算
                $video['end_time'] = time();
                $video['nums'] = ceil(($video['end_time'] - $video['start_time']) / 60);
                $video['coin'] = $video['unit_coin'] * $video['nums'];

                //更新视频通话表信息
                Db::name("live_video")->update($video);
                //生成消费订单
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
                    $infoStr = "do_stop";
                } else {
                    $infoStr = "stop_error";
                }
            } else {
                $infoStr = "do_nothing";
            }

            //创建ini文件
            $logFile = CMF_ROOT . 'data/runtime/zego.log';
            $file = fopen($logFile,"a") or die("Unable to open file!");
            //将内容写入文件
            fwrite($file, $room.":".$infoStr."========".$str."\n");
            //关闭文件
            fclose($file);

            exit("1");
        }else{
            exit("0");
        }
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
     * 随机视频聊天接通率
     * @throws \think\Exception
     */
    public function autoConnect(){
        $apply = rand(10,1000);
        $accept = rand(($apply * 0.9),$apply);//80-100%接通率
        $per = round(($accept * 100 / $apply),0);
        $data = [
            'connect_apply'     => $apply,
            'connect_accept'    => $accept,
            'connect_per'       => $per,
        ];
        $re = Db::name("live_anchor")->where('status',1)->where('connect_apply',0)->where('video_state',1)->limit(1)->update($data);
        var_dump($re);
    }
}
