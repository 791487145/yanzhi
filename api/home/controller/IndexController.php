<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;

class IndexController extends RestBaseController
{
    // api 首页
    public function index()
    {
        $this->success("恭喜您,API访问成功!", [
            'version' => '1.0.0',
            'doc'     => '颜值API'
        ]);
    }

    /**
     * 获取最新版本信息
     */
    public function version()
    {
        $device = $this->request->get('device');
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
        $version = Db::name("version")
            ->field("id code,version,explain,up_time uptime,".$device." url")
            ->where([$device.'_new' =>1])
            ->find();
        $this->success("获取成功",$version);
    }

    /**
     * 获取系统消息
     */
    public function message()
    {
        $page = $this->request->get('page', 1, 'intval');
        $pageSize = 10;//每次加载10条记录
        $start = ( $page - 1 ) * $pageSize;
        $list = Db::name('sys_message')
            ->field('title,pubtime,content')
            ->where( [ 'status' => 1 , 'pubtime' => ['lt',time()] ] )
            ->order('pubtime desc')
            ->limit($start,$pageSize)
            ->select();
        if (!$list || count($list) == 0){
            $this->error("暂无系统消息");
        }

        $this->success("获取成功",$list);
    }

    /**
     * 获取系统配置信息
     */
    public function systemInfo()
    {
        $version = $this->request->post('version');
        $device = $this->request->post('device');
        $downId = $this->request->post('down_id', 0, 'intval');

        $device = $this->checkDevice($device);

        $reData = [];
        
        //分享显示的信息
        $appSetting = cmf_get_option('app_settings');
        $reData['share_title'] = $appSetting['share_title'];
        $reData['share_des'] = $appSetting['share_des'];
        $reData['recommend_time'] = $appSetting['recommend_time'];
        $reData['login_movie'] = 'http://'.$_SERVER['HTTP_HOST'] .$appSetting['login_movie'];

        //获取当前版本审核状态
        $vInfo = Db::name("version")->where(['version'=> $version])->find();
        if (!$vInfo)
        {
            $this->error("版本信息错误");
        }
        $reData['ver_status'] = $vInfo[$device.'_status'];
        $reData['ver_is_new'] = $vInfo[$device.'_new'];

        $deviceId = $device == 'ios' ? 1 : 2;

        if ($downId > 0)//已有下载记录
        {
            $reData['down_id'] = $downId;
        }
        else
        {
            $ip = get_client_ip(0, true);   //IP地址
            $now = time();

            $dbDown = Db::name('down_log');
            //获取最近一条下载记录的信息
            $downInfo = $dbDown->where('device',$deviceId)->where('ip',$ip)->where('is_ins',0)->where('version',$vInfo['id'])
                ->where(['add_time'=>['>=',($now - 1800)]])//下载后30分钟内安装有效
                ->order('id desc')
                ->find();
            if ($downInfo)//已有记录的，修改安装状态
            {
                $reData['down_id'] = $downInfo['id'];
                $dbDown->where(['id'=>$downInfo['id']])->update(['is_ins'=>1,'ins_time'=>$now]);
            }
            else//否则添加记录
            {
                $reData['down_id'] = $dbDown->insertGetId([
                    'channel_id'    => 0,
                    'device'        => $deviceId,
                    'ip'            => $ip,
                    'add_time'      => $now,
                    'version'       => $vInfo['id'],
                    'is_ins'        =>1,
                    'ins_time'      =>$now
                ]);
            }
        }

        $whereMenu = ['status'=>1];
        if ($reData['ver_status'] == 0)//版本审核中
        {
            $whereMenu['status_exam'] = 1;
        }

        //------获取版本动态菜单信息 start------
        $menu = Db::name("app_menu")
            ->field("code,name,icon,type,url,page,mark,group")
            ->where($whereMenu)
            ->order('mark asc')
            ->select();
        if (count($menu) == 0)
        {
            $this->error("无菜单信息");
        }

        $tmpMenu = $reMenu = [];
        foreach ($menu as $key => $value)
        {
            $tmp = [
                'order'  => $value['mark'],
                'name'  => $value['name'],
                'code'  => $value['code'],
                'icon'  => $value['icon'] == '' ? '' : ('http://'.$_SERVER['HTTP_HOST'] .$value['icon']),
                'type'  => $value['type'],
                'url'  => $value['url'] == '' ? '' : ('http://'.$_SERVER['HTTP_HOST'] .$value['url']),
            ];
            $tmpMenu[$value['page']][$value['group']][] = $tmp;
        }
        foreach ($tmpMenu as $k => $v)
        {
            foreach ($v as $v1)
            {
                $reMenu[$k][] = $v1;
            }
        }

        $reData['menu'] = $reMenu;
        //------获取版本动态菜单信息 end------

        $reData['live_type'] = [
            ['type'=>'hot','name'=>'热门'],
            ['type'=>'new','name'=>'最新'],
            ['type'=>'recom','name'=>'推荐'],
            //['type'=>'priv','name'=>'私播'],
            ['type'=>'third_live','name'=>'精选'],
        ];

        $this->success("获取成功",$reData);
    }

    /**
     * 判断设备类型
     * @param $device
     * @return string
     */
    private function checkDevice($device)
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
        return $device;
    }
}
