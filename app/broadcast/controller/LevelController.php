<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\broadcast\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Validate;

/**
 * Class AnchorController
 * @package app\broadcast\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'直播管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'group',
 *     'remark' =>'直播管理'
 * )
 */
class LevelController extends AdminBaseController
{
    /**
     * 主播等级管理
     * @adminMenu(
     *     'name'   => '主播等级管理',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '主播等级管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $list = Db::name('live_level')->select();
        $this->assign('list', $list);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 添加主播等级
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 添加等级提交
     */
    public function addPost()
    {
        $validate = new Validate([
            'name'              => 'require',
            'icon'              => 'require',
            'live_reward'      => 'require|min:0|max:100',
            'single_reward'    => 'require|min:0|max:100',
            'point'             => 'require',
        ]);

        $validate->message([
            'name.require'              => '请填写等级名称',
            'icon.require'              => '请上传等级图标',
            'live_reward.require'       => '请填写直播平台提成',
            'live_reward.max'           => '直播平台提成不能超过100%',
            'live_reward.min'           => '直播平台提成不能低于0%',
            'single_reward.require'    => '请填写私播平台提成',
            'single_reward.max'         => '私播平台提成不能超过100%',
            'single_reward.min'         => '私播平台提成不能低于0%',
            'point.require'             => '请填写等级最低积分',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $result    = Db::name("live_level")->insert($data);
        if ($result === false) {
            $this->error("添加失败");
        }

        $this->success("添加成功！", url("broadcast/level/add"));
    }

    /**
     * 主播等级编辑页
     * @return mixed
     */
    public function edit()
    {
        $id = input('param.id', 0, 'intval');
        $level = Db::name('live_level')->where('id',$id)->find();
        $this->assign('level', $level);
        return $this->fetch();
    }

    /**
     * 编辑主播等级提交
     * @throws \think\Exception
     */
    public function editPost()
    {
        $validate = new Validate([
            'name'              => 'require',
            'icon'              => 'require',
            'live_reward'      => 'require|min:0|max:100',
            'single_reward'    => 'require|min:0|max:100',
            'point'             => 'require',
        ]);

        $validate->message([
            'name.require'              => '请填写等级名称',
            'icon.require'              => '请上传等级图标',
            'live_reward.require'       => '请填写直播平台提成',
            'live_reward.max'           => '直播平台提成不能超过100%',
            'live_reward.min'           => '直播平台提成不能低于0%',
            'single_reward.require'    => '请填写私播平台提成',
            'single_reward.max'         => '私播平台提成不能超过100%',
            'single_reward.min'         => '私播平台提成不能低于0%',
            'point.require'             => '请填写等级最低积分',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $result    = Db::name("live_level")->update($data);
        if ($result === false) {
            $this->error("保存失败");
        }

        $this->success("保存成功！", url("broadcast/level/edit",array('id'=>$data['id'])));
    }

    /**
     * 删除主播等级
     * @throws \think\Exception
     */
    public function delete()
    {
        $id = input('param.id', 0, 'intval');
        //判断等级是否被使用
        $use = Db::name('live_anchor')->where('level',$id)->count();
        if ($use > 0)
        {
            $this->error("等级已经使用");
        }

        $re = Db::name('live_level')->where('id',$id)->delete();
        if ($re)
        {
            $this->success('删除成功');
        }
        else
        {
            $this->error("删除失败");
        }
    }
}
