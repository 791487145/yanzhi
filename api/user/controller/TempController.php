<?php
// +----------------------------------------------------------------------
// | 临时接口文件
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use cmf\controller\RestBaseController;
use think\Db;

class TempController extends RestBaseController
{  
    /**
     * 根据ID判断用户在线状态
     */
    public function checkUserById() {
        $id = $this->request->param( "id" , 0 , 'intval' );
        if ($id > 0) {
            $data = ['name'=>"QY_".$id];
        } else {
            $data = ['name'=>["QY_2518","QY_1308","QY_7193","QY_6931"]];
        }
        $result = hook_one("user_jpush_state", $data);
        var_dump($result);
        exit();
    }
    /**
     * 判断用户在线状态
     */
    public function checkUserState() {
        $id = $this->request->param( "id" , 0 , 'intval' );
        $where = [
            'user_type'         => 2,
            //'login_state'       => 1,
            'is_virtual'        => 0,
            'is_zombie'         => 0,
            'id'                => ['>',$id]
        ];
        $userList = Db::name("user")->where($where)->limit(10)->select();
        $name = $logout = $login = [];
        $lastId = 0;
        foreach ($userList as $value) {
            $name[] = "QY_".$lastId;
            $lastId = $value['id'];
        }
        $data = ['name'=>$name];
        $result = hook_one("user_jpush_state", $data);
        if ($result['http_code'] == 200) {
            foreach ($result['body'] as $value) {
                $uid = substr($value['username'],3);
                $online = false;
                if ($value['statuscode'] == 0) {
                    foreach ($value['devices'] as $d) {
                        if ($d['online'] == true) {
                            $online = true;
                        }
                    }
                }
                if ($online) {
                    $login[] = $uid;
                } else {
                    $logout[] = $uid;
                }
            }
        }
        if (count($logout) > 0) {
            Db::name("user")->whereIn('id',$logout)->update(['login_state'=>0]);
//            var_dump(Db::name("user")->getLastSql());
        }
        if (count($login) > 0) {
            Db::name("user")->whereIn('id',$login)->update(['login_state'=>1]);
//            var_dump(Db::name("user")->getLastSql());
        }
        echo $lastId;
        exit();
    }

    /**
     * 推广、跳转域名微信检测
     */
    public function checkTuiDomainWeixin() {
        $main = Db::name("down_url")->where('id',1)->find();
        $jumpUrl = substr($main['jump_url'],7);
        $downUrl = substr($main['down_url'],7);
        $str = $jumpUrl . ',' . $downUrl;
        $otherUrl = Db::name("channel")->where(['domain'=>['neq','']])->whereNotNull("domain")->group("domain")->column("domain");
        if (count($otherUrl) > 0) {
            $str .= ",".implode(",",$otherUrl);
        }
        $reData = $this->checkWeixin($str);
        $delDomain = [];
        $msg = json_encode($reData)."||";
        $emailSetting = cmf_get_option('mail_setting');
        $mailTo = explode(',',$emailSetting['domain']);
        foreach ($reData as $value) {
            if ($value['result'] == 0) {
                if ($value['url'] == $jumpUrl) {//推广主域名被封
                    $msg .= "推广域名被封";
                    $result = cmf_send_email($mailTo, "推广主域名被封", "推广主域名被封:".$value['url']);
                    if ($result && empty($result['error'])) {
                        $msg .= 'succ；';
                    } else {
                        $msg .= 'fail['.$result['message'].']；';
                    }
                } elseif ($value['url'] == $downUrl) {//跳转域名被封
                    $msg .= "跳转域名被封";
                    $result = cmf_send_email($mailTo, "跳转域名被封", "跳转域名被封:".$value['url']);
                    if ($result && empty($result['error'])) {
                        $msg .= 'succ；';
                    } else {
                        $msg .= 'fail['.$result['message'].']；';
                    }
                } else { //渠道推广域名被封
                    $delDomain[] = $value['url'];
                }
            }
        }
        if (count($delDomain) > 0) {//存在被封的渠道推广域名
            $channel = Db::name("channel")->whereIn('domain',$delDomain)->column("name");
            $msg .= "渠道推广域名被封：".implode(",",$delDomain)."[渠道：".implode(",",$channel)."]";
            Db::name("channel")->whereIn('domain',$delDomain)->update(['domain'=>'']);
            $mailTo = explode(',',$emailSetting['domain']);
            $result = cmf_send_email($mailTo, "渠道推广域名被封", "被封渠道:".implode(",",$channel));
            if ($result && empty($result['error'])) {
                $msg .= 'succ；';
            } else {
                $msg .= 'fail['.$result['message'].']；';
            }
        }
        exit($msg);
    }

    /**
     * 调用验证接口
     * @param $urls 域名，多个使用英文逗号分隔
     * @return mixed 验证结果数组
     */
    private function checkWeixin($urls) {
        $url = "http://res.api.weixindomain.cn/wxlist?usercode=a7ab1aa9-f62f-4a40-866c-f6d9a174f063&password=JfnnlDI7RTiF9RgfG2JNCw==";
        $data = http_build_query(['urls'=>$urls]);
        $header = ['Content-type: application/x-www-form-urlencoded'];
        $msg = $this->curlPost($url,$data,$header);
        $reData = json_decode($msg, true);
        if ($reData[0]['result'] == '502') {
            $emailSetting = cmf_get_option('mail_setting');
            $mailTo = explode(',',$emailSetting['domain']);
            $result = cmf_send_email($mailTo, "域名检测502", "超过查询限制（用户账户过期）");
        }
        return $reData;
    }
    /**
     * 落地页域名微信检测
     */
    public function checkDomainWeixin(){
        $info = Db::name("down_url")->where('id',1)->find();
        $reData = $this->checkWeixin($info['land_url']);
        $delDomain = $saveDomain = [];
        foreach ($reData as $value) {
            if ($value['result'] == 0) {
                $delDomain[] = $value['url'];
            } else {
                $saveDomain[] = $value['url'];
            }
        }
        $msg = json_encode($reData)."||";
        $title = $content = "";
        if (count($delDomain) > 0) {
            //删除后的域名入库
            Db::name("down_url")->where('id',1)->update(['land_url'=>implode(',',$saveDomain)]);
            //发送邮件删除域名
            $title .= "落地页域名被封,";
            $content .= "被封落地页域名：".implode(',',$delDomain)."；";
            $msg .= "del:".implode(',',$delDomain).";";
        }
        if (count($saveDomain) < 5) {
            //发送邮件提示落地页域名不足
            $title .= "域名不足,";
            $content .= "剩余域名还有".count($saveDomain)."个";
            $msg .= "num:".count($saveDomain).";";
        }
        if ($title != "") {
            $emailSetting = cmf_get_option('mail_setting');
            $mailTo = explode(',',$emailSetting['domain']);
            $result = cmf_send_email($mailTo, $title, $content);
            if ($result && empty($result['error'])) {
                $msg .= 'succ';
            } else {
                $msg .= 'fail['.$result['message'].']';
            }
        }
        exit($msg);
    }
  
    /**
     * 10条视频随机增加10-20查看数
     * @throws \think\Exception
     */
    public function addViews() {
        $count = rand(5,10);
        $num = rand(10,20);//随机增加10-20观看数
        $ids = Db::name("user_video")->field("*,rand() vorder")->order("vorder")->limit($count)->column('id');
        Db::name("user_video")->whereIn('id',$ids)->setInc("views",$num);
        echo json_encode($ids)."---->".$num;
        exit();
    }
  
    /**
     * 更新附近的人距离数据
     * @throws \think\Exception
     */
    public function longEdit() {
        $list = Db::name("user")->whereLike('recom_type','%"1"%')->field("*,rand() rorder")->order('rorder')->select();
        $now = time();
        foreach ( $list as $key => $value ) {
            $more = json_decode($value['more'],true);
            $more['long'] = rand(1,30)*100;
            $now = $now - rand(10,600);
            $data = [
                'more'                  => json_encode($more),
                'last_login_time'      => $now
            ];
            Db::name("user")->where('id',$value['id'])->update($data);
            var_dump($value['id'].'--'.$more['long'].'--'.date('Y-m-d H:i:s',$now));
        }
        exit();
    }

    /**
     * 更新直播标签
     */
    public function tagEdit() {
        $tagArr = ['tag1','tag2','tag3','tag4','tag5','tag6','tag7','tag8','tag9','taga','tagb'];
        $num = count($tagArr) * 3;//每个标签设置3个直播间
        $list = Db::name("live_room")->where('live_type',1)->where('live_state',1)->field("*,rand() rorder")->order('rorder')->limit($num)->select();
        $n = ceil( count($list) / count($tagArr) );
        $idArr = [];
        foreach ( $list as $k => $v ) {
            $i = floor( $k / $n );
            $idArr[$i][] = $v['id'];
        }
        Db::name("live_room")->whereNotNull('live_tag')->update(['live_tag'=>NULL]);
        foreach ( $idArr as $k => $v ) {
            Db::name("live_room")->whereIn('id',$v)->update(['live_tag'=>$tagArr[$k]]);
            var_dump(Db::name("live_room")->getLastSql());
            //echo implode(',',$v).'---'.$tagArr[$k].';';
        }
        exit();
    }
    
    /**
     * JPushInfo
     */
    public function jpushInfo()
    {
        $id = $this->request->param( "id" , 0 , 'intval' );
        $data = Db::name("user")->where('id',$id)->find();
        if (!$data) {
            exit("finish");
        }
        echo $data['id']+1;//下一条记录的ID
        $more = json_decode($data['more'],true);
        $extras = [
            'vip_type'  => empty($more['vip_type']) ? 2 : $more['vip_type']
        ];
        if ($data['avatar'] != '' &&  strpos($data['avatar'],'http') === false) {
            $data['avatar'] = '/www/wwwroot/yanzhi/public' .$data['avatar'];
        } else {//将网络文件放到临时目录

            $userIdStr = (string)$id;
            $num_strlength = count($userIdStr);
            if (10 > $num_strlength) {
                $userIdStr = str_pad($userIdStr, 10, "0", STR_PAD_LEFT);
            }

            $idUrl =  [
                substr($userIdStr, 0, 3),
                substr($userIdStr, 3, 3),
                substr($userIdStr, 6)
            ];

            $fPath = '/www/wwwroot/yanzhi/public/upload/head/' . $idUrl[0] . '/' . $idUrl[1];

            $fName = $idUrl[2] . '.jpg';
            $tmp = $this->getFile($data['avatar'],$fPath,$fName);
            $data['avatar'] = $tmp['save_path'];
        }

        $param = [
            'type'      => "image",
            'url'       => $data['avatar']
        ];
        $result = hook_one("upload_jpush", $param);

        if ($result['http_code'] == 200) {//图片上传成功
            $mediaId= $result['body']['media_id'];

            $udata = [
                'name'      =>'QY_'.$data['id'],
                'pass'      => $data['id'].'DINGTJIAYUAN'
            ];
            $uResult = hook_one("user_jpush_reg", $udata);
            //var_dump($uResult);

            $pData = [
                "name"  => 'QY_'.$data['id'],
                "data"  => [
                    "avatar"    => $mediaId,
                    "nickname"  => $data['user_nickname'],
                    'gender'    => $data['sex'],
                    'extras'    => $extras
                ]
            ];
            $eResult = hook_one("user_jpush_edit", $pData);
        } else {
        }
        exit();
    }
    
    private function getFile($url, $save_dir = '', $filename = '', $type = 0) {
        if (trim($url) == '') {
            return false;
        }
        if (trim($save_dir) == '') {
            $save_dir = './';
        }
        if (0 !== strrpos($save_dir, '/')) {
            $save_dir.= '/';
        }
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return false;
        }
        //获取远程文件所采用的方法
        if ($type) {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $content = curl_exec($ch);
            curl_close($ch);
        } else {
            ob_start();
            readfile($url);
            $content = ob_get_contents();
            ob_end_clean();
        }
        $size = strlen($content);
        //文件大小
        $fp2 = @fopen($save_dir . $filename, 'a');
        fwrite($fp2, $content);
        fclose($fp2);
        unset($content, $url);
        return array(
            'file_name' => $filename,
            'save_path' => $save_dir . $filename
        );
    }

    /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     */
    private function curlPost($url, $postFields, $header = array( 'Content-Type: application/json; charset=utf-8' ))
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields);  //该curl_setopt可以向header写键值对
        //curl_setopt( $ch, CURLOPT_HEADER, false); // 不返回头信息
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
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
}