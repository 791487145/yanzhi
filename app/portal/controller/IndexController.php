<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class IndexController extends HomeBaseController
{
    /**
     * 平台首页
     * @return mixed
     */
    public function index()
    {
        exit("网站建设中");
        return $this->fetch(':index');
    }

    /**
     * 进入下载页面
     */
    public function app()
    {
        $userId = $this->request->param('m', 0, 'intval');      //渠道/推广员ID
        $p = $this->request->param('p', 0, 'intval');           //下载模板
        //根据p值，获取模板ID
        switch ($p)
        {
            case "1":
                $pageId = $p;
                break;
            default:
                $pageId = rand(0,2);//随机抽取模板
        }

        $url = "/portal/index/down?m=".$userId."&t=".time();

        $this->assign('url', $url);
        return $this->fetch('/down/page_'.$pageId);
    }

    /**
     * 下载操作,将下载记录写入数据库
     */
    public function down()
    {
        $device = $this->getDevice();                              //设备类型
        $ip = get_client_ip(0, true);                             //IP地址
        $m = $this->request->param('m', 0, 'intval');      //渠道/推广员ID

        //根据设备类型获取最新版本下载地址
        $version = Db::name('version')->where([$device.'_status'=>1,$device.'_new'=>1])->find();

        if ($m > 0)//将渠道下载记录写入数据库
        {
            Db::name('down_log')->insert([
                'channel_id'    => $m,
                'device'        => $device,
                'ip'            => $ip,
                'add_time'      => time(),
                'version'       => $version['id']
            ]);
        }

        //判断是否微信
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $url = $version['wx'];
        } else {
            $url = $version[$device];
        }
        //跳转到实际下载链接地址
        header("Location: ".$url);
        exit();
//        header('location: '.$url);
//        echo '<script>window.location.href="'.$url.'"</script>';
    }

    /**
     * 判断访问设备类型
     * @return string
     */
    private function getDevice()
    {
        //获取USER AGENT
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        //分析数据
        $is_pc = (strpos($agent, 'windows nt')) ? true : false;
        $is_iphone = (strpos($agent, 'iphone')) ? true : false;
        $is_ipad = (strpos($agent, 'ipad')) ? true : false;
        $is_android = (strpos($agent, 'android')) ? true : false;
        //输出数据
        if ($is_pc) {
//            return "pc";
            return "android";
        }
        if ($is_iphone) {
//            return "iphone";
            return "ios";
        }
        if ($is_ipad) {
//            return "ipad";
            return "ios";
        }
        if ($is_android) {
            return "android";
        }
    }
}
