<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Validate;

class AppmenuController extends AdminBaseController
{
    /**
     * APP个人中心菜单管理
     * @adminMenu(
     *     'name'   => 'APP个人中心菜单管理',
     *     'parent' => 'admin/Setting/app',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => 'APP个人中心菜单管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $list     = Db::name("app_menu")->order('page desc,`group` asc,mark asc')->select();
        $this->assign("list", $list);

        return $this->fetch();
    }

    /**
     * 添加菜单页面
     * @adminMenu(
     *     'name'   => '添加菜单页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加菜单页面',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 添加菜单提交保存
     * @adminMenu(
     *     'name'   => '添加菜单提交保存',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加菜单提交保存',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        $validate = new Validate([
            'page' => 'require',
            'name' => 'require',
            'code' => 'require',
        ]);

        $validate->message([
            'page.require' => '请填写页面标识',
            'name.require' => '请填写菜单名称',
            'code.require' => '请填写CODE',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $data['group'] = $this->request->post('group', 0, 'intval');
        $data['mark'] = $this->request->post('mark', 99, 'intval');

        if ($data['type'] == 2 && $data['url'] == '')
        {
            $this->error("请填写链接地址");
        }

        $result    = Db::name("app_menu")->insert($data);
        if ($result === false) {
            $this->error("添加失败");
        }

        $this->success("添加成功！", url("appmenu/add"));
    }

    /**
     * 编辑菜单页面
     * @adminMenu(
     *     'name'   => '编辑菜单页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加菜单页面',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id        = $this->request->param('id', 0, 'intval');
        $menu = Db::name("app_menu")->where('id',$id)->find();
        $this->assign('menu', $menu);
        return $this->fetch();
    }

    /**
     * 编辑菜单提交保存
     * @adminMenu(
     *     'name'   => '编辑菜单提交保存',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑友情链接提交保存',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $validate = new Validate([
            'page' => 'require',
            'name' => 'require',
            'code' => 'require',
        ]);

        $validate->message([
            'page.require' => '请填写页面标识',
            'name.require' => '请填写菜单名称',
            'code.require' => '请填写CODE',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $data['group'] = $this->request->post('group', 0, 'intval');
        $data['mark'] = $this->request->post('mark', 99, 'intval');

        if ($data['type'] == 2 && $data['url'] == '')
        {
            $this->error("请填写链接地址");
        }

        $result    = Db::name("app_menu")->update($data);
        if ($result === false) {
            $this->error("保存失败");
        }

        $this->success("保存成功！", url("appmenu/edit",array('id'=>$data['id'])));
    }

    /**
     * APP个人中心下载管理
     * @adminMenu(
     *     'name'   => 'APP个人中心下载管理',
     *     'parent' => 'admin/Setting/app',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => 'APP个人中心下载管理',
     *     'param'  => ''
     * )
     */
    public function down() {
        $list     = Db::name("down_url")->select();
        $this->assign("list", $list);
        return $this->fetch();
    }
    /**
     * 编辑下载页面
     * @adminMenu(
     *     'name'   => '编辑下载页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加下载页面',
     *     'param'  => ''
     * )
     */
    public function downEdit()
    {
        $id        = $this->request->param('id', 0, 'intval');
        $data = Db::name("down_url")->where('id',$id)->find();
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 编辑菜单提交保存
     * @adminMenu(
     *     'name'   => '编辑菜单提交保存',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑友情链接提交保存',
     *     'param'  => ''
     * )
     */
    public function downEditPost()
    {
        $validate = new Validate([
            'name'      => 'require',
            'jump_url' => 'require',
            'down_url' => 'require',
            'land_url' => 'require',
        ]);
        $validate->message([
            'name.require'      => '请填写名称',
            'jump_url.require' => '请填写跳转页域名',
            'down_url.require' => '请填写下载页域名',
            'land_url.require' => '请填写落地页域名',
        ]);
        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $result    = Db::name("down_url")->update($data);
        if ($result === false) {
            $this->error("保存失败");
        }

        $this->success("保存成功！", url("appmenu/downedit",array('id'=>$data['id'])));
    }
}