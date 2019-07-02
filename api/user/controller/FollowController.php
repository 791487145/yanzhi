<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------

namespace api\user\controller;

use cmf\controller\RestUserBaseController;
use think\Db;

class FollowController extends RestUserBaseController
{
    private $pageSize = 10;//每页加载数量

    /**
     * 获取关注列表
     */
    public function getlist()
    {
        $user_id = $this->request->param('id', 0, 'intval');
        if ($user_id == 0) {
            $user_id = $this->userId;
        }
        $page = $this->request->param('page', 1, 'intval');
        $start = $this->pageSize * ( $page - 1 );

        $reData = Db::name('user_follow')->alias('f')
            ->join('__USER__ u', 'f.user_id =u.id')
            ->field('u.id,u.user_nickname nickname,u.sex,u.avatar,f.follow_time,u.signature')
            ->where([ 'f.fans_id' => $user_id , 'f.status' => 1 ])
            ->order('f.follow_time desc')
            ->limit($start,$this->pageSize)
            ->select();
        $reData = $this->avatar($reData);

        $this->success('获取成功', $reData);
    }

    /**
     * 获取粉丝列表
     */
    public function getfans()
    {
        $user_id = $this->request->param('id', 0, 'intval');
        if ($user_id == 0) {
            $user_id = $this->userId;
        }
        $page = $this->request->param('page', 1, 'intval');
        $start = $this->pageSize * ( $page - 1 );

        $reData = Db::name('user_follow')->alias('f')
            ->join('__USER__ u', 'f.fans_id =u.id')
            ->field('u.id,u.user_nickname nickname,u.sex,u.avatar,f.follow_time,u.signature')
            ->where([ 'f.user_id' => $user_id , 'f.status' => 1 ])
            ->order('f.follow_time desc')
            ->limit($start,$this->pageSize)
            ->select();

        $reData = $this->avatar($reData);

        $userFallow = Db::name('user_follow')->where('fans_id',$user_id)->where('status',1)->select();
        $followIDs = [];//当前用户关注了的用户ID列表
        foreach ($userFallow as $value)
        {
            $followIDs[] = $value['user_id'];
        }
        foreach ($reData as $key => $value)
        {
            if (in_array($value['id'],$followIDs))
            {
                $value['follow'] = 1;
            } else {
                $value['follow'] = 0;
            }
            $reData[$key] = $value;
        }

        $this->success('获取成功', $reData);
    }

    /**
     * 头像处理
     * @param $list
     * @return mixed
     */
    private function avatar($list)
    {
        foreach ($list as $key => $value){
            if ($value['avatar'] != '' &&  strpos($value['avatar'],'http://') === false){
                $value['avatar'] = 'http://'.$_SERVER['HTTP_HOST'] .$value['avatar'];
                $list[$key] = $value;
            }
        }
        return $list;
    }

    /**
     * 添加关注
     */
    public function add()
    {
        $user_id             = $this->request->param('id', 0, 'intval');
        if ($user_id == 0){
            $this->error("参数错误");
        }

        $data = ['fans_id'=>$this->userId,'user_id'=>$user_id];

        $follow = Db::name('user_follow')->where($data)->find();
        if ($follow)//已有关注信息
        {
            $data['status'] = '1';
            $data['follow_time'] = time();
            $re = Db::name('user_follow')->where(['id'=>$follow['id']])->update($data);
        }
        else
        {
            $data['status'] = '1';
            $data['follow_time'] = time();
            $re = Db::name('user_follow')->insert($data);
        }
        if (!$re){
            $this->error("关注失败");
        }
        $this->success("关注成功");
    }

    /**
     * 取消关注
     */
    public function cancel()
    {
        $user_id             = $this->request->param('id', 0, 'intval');
        if ($user_id == 0){
            $this->error("参数错误");
        }

        $data = ['fans_id'=>$this->userId,'user_id'=>$user_id];

        $follow = Db::name('user_follow')->where($data)->find();
        if ($follow)//已有关注信息
        {
            $data['status'] = '0';
            $data['follow_time'] = time();
            $re = Db::name('user_follow')->where(['id'=>$follow['id']])->update($data);
            if (!$re){
                $this->error("取消失败");
            }
            $this->success("取消成功");
        }
        else{
            $this->error("尚未关注");
        }
    }
}
