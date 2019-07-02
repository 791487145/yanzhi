<?php
// +----------------------------------------------------------------------
// | 虎虎网络科技
// +----------------------------------------------------------------------
// | Author: Lixue
// +----------------------------------------------------------------------
namespace plugins\easemob;//Demo插件英文名，改成你的插件英文就行了
use cmf\lib\Plugin;

/**
 * MobileCodeDemoPlugin
 */
class EasemobPlugin extends Plugin
{

    public $info = [
        'name'        => 'Easemob',
        'title'       => '环信即时通讯插件',
        'description' => '环信即时通讯插件',
        'status'      => 1,
        'author'      => 'Lixue',
        'version'     => '1.0'
    ];

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
     * 授权注册模式 || 批量注册
     *
     * @param $options['username'] 用户名
     * @param $options['password'] 密码
     *          批量注册传二维数组
     */
    public function registerEasemob($options) {
        $config        = $this->getConfig();
        $url = $config['url'] . "users";
        $access_token = $this->getTokenEasemob ();
        $header [] = 'Authorization: Bearer ' . $access_token;
        $result = $this->postCurl ( $url, $options, $header );
        return $result;
    }

    /**
     * 发送消息
     *
     * @param string $from_user
     *          发送方用户名
     * @param array $username
     *          array('1','2')
     * @param string $target_type
     *          默认为：users 描述：给一个或者多个用户(users)或者群组发送消息(chatgroups)
     * @param string $content
     * @param array $ext
     *          自定义参数
     */
    public function sendEasemob($option) {//$from_user = "admin", $username, $content, $target_type = "users", $ext
        $config        = $this->getConfig();
//        $option ['target_type'] = $target_type;
//        $option ['target'] = $username;
//        $params ['type'] = "txt";
//        $params ['msg'] = $content;
//        $option ['msg'] = $params;
//        $option ['from'] = $from_user;
//        $option ['ext'] = $ext;
        $url = $config['url'] . "messages";
        $access_token = $this->getTokenEasemob ();
        $header [] = 'Authorization: Bearer ' . $access_token;
        $result = $this->postCurl ( $url, $option, $header );
        return $result;
    }

    /**
     * 上传图片、视频等
     * @param $option
     * @return mixed
     */
    public function fielsEasemob($option)
    {
        $config        = $this->getConfig();
        $url = $config['url'] . 'chatfiles';
        $access_token = $this->getTokenEasemob ();

        $header [] = 'Content-type: multipart/form-data';
        $header [] = 'Authorization: Bearer ' . $access_token;
        $header [] = "restrict-access:true";

        $curl = curl_init();
        curl_setopt ( $curl, CURLOPT_URL, $url ); // 要访问的地址
        curl_setopt ( $curl, CURLOPT_POST,true );
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, FALSE ); // 对认证证书来源的检查
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, FALSE ); // 从证书中检查SSL加密算法是否存在
        curl_setopt ( $curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)' ); // 模拟用户使用的浏览器
        curl_setopt ( $curl, CURLOPT_HEADER,0);
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER,1);
        curl_setopt ( $curl, CURLOPT_CUSTOMREQUEST,'POST');
        curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header ); // 设置HTTP头
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt ( $curl, CURLOPT_POSTFIELDS,['file'=>$option]);
        $result = curl_exec ( $curl );
        curl_close($curl);
        $return = json_decode($result,true);
        return $return['entities'];
    }

    /**
     * 获取Token
     */
    public function getTokenEasemob() {
        //print_r($url);exit;
        $config        = $this->getConfig();
        $option ['grant_type'] = "client_credentials";
        $option ['client_id'] = $config['client_id'];
        $option ['client_secret'] = $config['client_secret'];
        $url = $config['url'] . "token";
        $file_path = CMF_ROOT . 'data/runtime/huanxin_token.log';
        $fp = @fopen ( $file_path, 'r' );
        if ($fp) {
            $arr = json_decode ( fgets ( $fp ) , true);
            if (time () < $arr ['endtime'] ) {//token未到期，直接返回token值
                fclose ( $fp );
                return $arr ['access_token'];
            }
        }
        $result = $this->postCurl ( $url, $option, $head = 0 );
        $result = json_decode($result,true);
        $result['endtime'] = time() + $result['expires_in'] - 300;//设置结束时间为有效期结束前5分钟
        $fp = @fopen ( $file_path, 'w' );
        @fwrite ( $fp, json_encode ( $result ) );
        fclose ( $fp );
        return $result ['access_token'];
    }
    /**
     * CURL Post
     */
    private function postCurl($url, $option, $header = 0, $type = 'POST') {
        $curl = curl_init (); // 启动一个CURL会话
        curl_setopt ( $curl, CURLOPT_URL, $url ); // 要访问的地址
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, FALSE ); // 对认证证书来源的检查
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, FALSE ); // 从证书中检查SSL加密算法是否存在
        curl_setopt ( $curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)' ); // 模拟用户使用的浏览器
        curl_setopt ( $curl, CURLOPT_HEADER, 0 );//参数设置，是否显示头部信息，1为显示，0为不显示
        if (! empty ( $option )) {
            $options = json_encode ( $option );
            curl_setopt ( $curl, CURLOPT_POSTFIELDS, $options ); // Post提交的数据包
        }
        curl_setopt ( $curl, CURLOPT_TIMEOUT, 30 ); // 设置超时限制防止死循环
        if ($header != 0){
            curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header ); // 设置HTTP头
        }
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 ); // 获取的信息以文件流的形式返回
        switch($type){
            case "GET":
                curl_setopt($curl,CURLOPT_HTTPGET,true);
                break;
            case "POST":
                curl_setopt($curl,CURLOPT_POST,true);
                break;
            case "PUT"://使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。这对于执行"DELETE" 或者其他更隐蔽的HTT
                curl_setopt($curl,CURLOPT_CUSTOMREQUEST,"PUT");
                break;
            case "DELETE":
                curl_setopt($curl,CURLOPT_CUSTOMREQUEST,"DELETE");
                break;
        }
        $result = curl_exec ( $curl ); // 执行操作
        curl_close ( $curl ); // 关闭CURL会话
        return $result;
    }
}