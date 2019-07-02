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
     * 10条视频随机增加10-20查看数
     * @throws \think\Exception
     */
    public function addViews() {
        $count = rand(5,10);
        $num = rand(10,20);//随机增加10-20观看数
        $ids = Db::name("user_video")->order("rand()")->limit($count)->column('id');
        Db::name("user_video")->whereIn('id',$ids)->setInc("views",$num);
        echo json_encode($ids)."---->".$num;
        exit();
    }
  
    /**
     * 更新附近的人距离数据
     * @throws \think\Exception
     */
    public function longEdit() {
        $list = Db::name("user")->whereLike('recom_type','%"1"%')->select();
        foreach ( $list as $value ) {
            $more = json_decode($value['more'],true);
            $more['long'] = rand(50,3000);
            Db::name("user")->where('id',$value['id'])->update(['more'=>json_encode($more)]);
            var_dump($value['id'].'--'.$more['long']);
        }
        exit();
    }

    /**
     * 更新直播标签
     */
    public function tagEdit() {
        $tagArr = ['tag1','tag2','tag3','tag4','tag5','tag6','tag7','tag8','tag9','taga','tagb'];
        $num = count($tagArr) * 3;//每个标签设置3个直播间
        $list = Db::name("live_room")->where('live_type',1)->where('live_state',1)->order('rand()')->limit($num)->select();
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
        $data = Db::name("user")->where(['id'=>['>',$id]])->order('id asc')->find();
        if (!$data) {
            exit("finish");
        }
        echo $data['id'];
        $more = json_decode($data['more'],true);
        $extras = [
            'vip_type'  => empty($more['vip_type']) ? 2 : $more['vip_type']
        ];
        if ($data['avatar'] != '' &&  strpos($data['avatar'],'http') === false) {
            $data['avatar'] = '/www/wwwroot/yanzhi/public' .$data['avatar'];
        } else {//将网络文件放到临时目录
            $fPath = "/www/wwwroot/yanzhi/public/upload/head";
            $fName = "tmp.jpg";
            if(file_exists($fPath.'/'.$fName))unlink($fPath.'/'.$fName);
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
                'pass'      =>'QY_'.$data['id'].'HUHU'
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
            //var_dump($eResult);
        } else {
            //var_dump($result);
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
}