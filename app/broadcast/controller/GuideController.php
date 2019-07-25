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
 * Class GuideController
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
class GuideController extends AdminBaseController
{
    /**
     * 公会管理
     * @adminMenu(
     *     'name'   => '公会管理',
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
        $request = input('request.');
        $db = Db::name('guide');
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $db->whereOr(['code|name|cdr|mobile|qq|weixin'=>['like','%'.$keyword.'%']]);
        }
        $list = $db->paginate(10);
        // 获取分页显示
        $page = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 添加公会页面
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 添加公会提交
     */
    public function addPost()
    {
        $validate = new Validate([
            'code'              => 'require',
            'name'              => 'require',
            'u.user_login'     => 'require',
            'u.user_pass'      => 'require',
            'ratio'             => 'require|min:0|max:100',
            'cdr'               => 'require',
            'pay_name'          => 'require',
            'pay_type'          => 'require',
            'pay_account'      => 'require',
        ]);

        $validate->message([
            'code.require'              => '请填写公会代码',
            'name.require'              => '请填写公会名称',
            'u.user_login.require'     => '请填写登录账号',
            'u.user_pass.require'      => '请填写登录密码',
            'ratio.require'             => '请填写公会提成',
            'ratio.max'                 => '直播平台提成不能超过100%',
            'ratio.min'                 => '直播平台提成不能低于0%',
            'cdr.require'               => '请填写会长姓名',
            'pay_name.require'          => '请填写收款人姓名',
            'pay_type.require'          => '请选择收款账户类型',
            'pay_account.require'       => '请填写收款账户',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if ($data['mobile'].$data['qq'].$data['weixin'] == '') {
            $this->error("请填写至少一种会长联系方式");
        }
        $user = $data['u'];
        unset($data['u']);
        $check = Db::name('user')->where('user_login',$user['user_login'])->find();
        if ($check) {
            $this->error("登录账号已存在");
        }

        $user['user_pass'] = cmf_password($user['user_pass']);
        $user['user_type'] = 4;
        $userId = Db::name('user')->insertGetId($user);

        $data['user_id'] = $userId;
        $data['status']  = 1;//默认审核通过
        $result    = Db::name("guide")->insert($data);
        if ($result === false) {
            $this->error("添加失败");
        }

        $this->success("添加成功！", url("broadcast/guide/add"));
    }

    /**
     * 公会编辑页
     * @return mixed
     */
    public function edit()
    {
        $id = input('param.id', 0, 'intval');
        $data = Db::name('guide')->where('id',$id)->find();
        $user = Db::name('user')->where('id',$data['user_id'])->find();
        $this->assign('data', $data);
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * 编辑公会提交
     * @throws \think\Exception
     */
    public function editPost()
    {
        $validate = new Validate([
            'code'              => 'require',
            'name'              => 'require',
            'ratio'             => 'require|min:0|max:100',
            'cdr'               => 'require',
            'pay_name'          => 'require',
            'pay_type'          => 'require',
            'pay_account'      => 'require',
        ]);

        $validate->message([
            'code.require'              => '请填写公会代码',
            'name.require'              => '请填写公会名称',
            'ratio.require'             => '请填写公会提成',
            'ratio.max'                 => '直播平台提成不能超过100%',
            'ratio.min'                 => '直播平台提成不能低于0%',
            'cdr.require'               => '请填写会长姓名',
            'pay_name.require'          => '请填写收款人姓名',
            'pay_type.require'          => '请选择收款账户类型',
            'pay_account.require'       => '请填写收款账户',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if ($data['mobile'].$data['qq'].$data['weixin'] == '') {
            $this->error("请填写至少一种会长联系方式");
        }

        $user = $data['u'];
        unset($data['u']);
        if (!empty($user['user_pass']) && $user['user_pass'] != '') {
            $user['user_pass'] = cmf_password($user['user_pass']);
            Db::name('user')->update($user);
        }

        $result    = Db::name("guide")->update($data);
        if ($result === false) {
            $this->error("保存失败");
        }

        $this->success("保存成功！", url("broadcast/guide/edit",array('id'=>$data['id'])));
    }

    /**
     * 公会解约/续约
     * @throws \think\Exception
     */
    public function status()
    {
        $id = input('param.id', 0, 'intval');
        $status = input('param.status', 0, 'intval');
        $re = Db::name('guide')->where('id',$id)->update(['status'=>$status]);
        if ($re) {
            $this->success('操作成功');
        }
        $this->error("操作失败");
    }
}
