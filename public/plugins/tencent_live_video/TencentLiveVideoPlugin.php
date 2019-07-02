<?php
// +----------------------------------------------------------------------
// | 虎虎网络科技
// +----------------------------------------------------------------------
// | Author: Lixue <75077317@qq.com>
// +----------------------------------------------------------------------
namespace plugins\tencent_live_video;
use cmf\lib\Plugin;

/**
 * 腾讯云直播插件
 */
class TencentLiveVideoPlugin extends Plugin
{

    public $info = [
        'name'        => 'TencentLiveVideo',
        'title'       => '腾讯云直播插件',
        'description' => '腾讯云直播插件',
        'status'      => 1,
        'author'      => 'Lixue',
        'version'     => '1.0'
    ];
    private $playUrl = "live.squareorange.cn";

    public $has_admin = 0;//插件是否有后台管理界面

    public function install() //安装方法必须实现
    {
        return true;//安装成功返回true，失败false
    }

    public function uninstall() //卸载方法必须实现
    {
        return true;//卸载成功返回true，失败false
    }
    
    /**
     * 获取推流地址
     * @param userId 用户ID
     * @return String url */
    function getLiveVideoPushUrl($live_code)
    {
        $config        = $this->getConfig();
        $time = time() + ( $config['time'] * 3600 );
        $txTime = base_convert($time,10,16);
        $txTime = strtoupper($txTime);
        $livecode = $config['biz_id']."_".$live_code; //直播码
        $txSecret = md5($config['live_key'].$livecode.$txTime);
        $ext_str = "?".http_build_query(array(
                "bizid"=> $config['biz_id'],
                "txSecret"=> $txSecret,
                "txTime"=> $txTime
            ));
        return [
            "stream"=>$livecode,
            "push"=>"rtmp://".$config['biz_id'].".livepush.myqcloud.com/live/".$livecode.(isset($ext_str) ? $ext_str : ""),
            "pull_wheat"=>"http://".$this->playUrl."/live/".$livecode.".flv"
        ];
    }

    /**
     * 获取播放地址，并判断直播状态
     * @param userId 主播用户ID
     * @return String url */
    function getLiveVideoPlayUrl($livecode){
        $config        = $this->getConfig();
        $time = time();

        $url = "http://fcgi.video.qcloud.com/common_access"
            . '?appid=' . $config['app_id']
            . '&interface=' . 'Live_Channel_GetStatus'
            . '&Param.s.channel_id=' . $livecode
            . '&t=' . $time
            . '&sign=' . md5($config['api_key'].$time);
        $re = json_decode(self::curlGet( $url ), true);

        if ($re['ret'] == 0 && $re['output'][0]['status'] == 1 )//接口调用成功
        {
            return [
                'ret'       => true,
                'data'      => [
                    'stream'    => $livecode,
//                    "rtmp"      => "rtmp://".$this->playUrl."/live/".$livecode,
                    "url"       => "http://".$this->playUrl."/live/".$livecode.".flv",
//                    "hls"       => "http://".$this->playUrl."/live/".$livecode.".m3u8"
                ]
            ];
        }
        else
        {
            return ['ret' => false,  'msg'   => "直播结束"];
        }
    }

    /**
     * 结束直播
     * @param $userId
     * @return bool
     */
    function setLiveVideoStop($live_code)
    {
        $config        = $this->getConfig();
        $livecode = $config['biz_id']."_".$live_code; //直播码
        $time = time();

        $url = "http://fcgi.video.qcloud.com/common_access"
            . '?appid=' . $config['app_id']
            . '&interface=' . 'Live_Channel_SetStatus'
            . '&Param.s.channel_id=' . $livecode
            . '&Param.s.status=' . '2'
            . '&t=' . $time
            . '&sign=' . md5($config['api_key'].$time);
        $re = json_decode(self::curlGet( $url ), true);
        return ($re['ret'] == 0) ? true : false ;
    }
    
    /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     */
    private function curlPost($url,$postFields)
    {
        $postFields = json_encode($postFields);
        $ch = curl_init ();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8'
            )
        );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt( $ch, CURLOPT_TIMEOUT,1);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
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
}