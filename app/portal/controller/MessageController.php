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

use cmf\controller\AdminBaseController;
use think\Db;
use think\Validate;

class MessageController extends AdminBaseController
{
    /**
     * 系统消息列表
     */
    public function index()
    {
        $request = input('request.');

        $query =  Db::name("sys_message");

        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $this->assign('keyword', $keyword);
            $query->whereOr(['title|content'=>['like', "%".$keyword."%"]]);
        }

        $result = $query->order('id desc')->paginate(10);;

        $this->assign('list', $result->items());
        $this->assign('page', $result->render());

        return $this->fetch();
    }

    /**
     * 添加系统消息
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 添加系统消息提交
     */
    public function addPost()
    {
        $validate = new Validate([
            'title'              => 'require',
            'content'              => 'require',
        ]);

        $validate->message([
            'title.require'             => '请填写标题',
            'content.require'           => '请填写内容',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $ins = [
            'title'     => $data['title'],
            'content'   => $data['content'],
            'status'    => $data['status'],
            'addtime'   => time(),
        ];
        if ($data['send'] == 1)//立即推送
        {
            $ins['pubtime'] = $ins['addtime'];
        }

        $id = Db::name("sys_message")->insertGetId($ins);
        if ($id === false) {
            $this->error("添加失败");
        }
        $msg = "添加成功";
        if ($data['send'] == 1)
        {
            $sendData = [
                'title'     => $data['title'],
                'msg'       => $data['content'],
                'type'      => 1
            ];
            $push = hook_one("push_msg", $sendData);
            if ($push)
            {
                $msg .= ",推送成功";
            }
            else
            {
                $msg .= ",推送失败";
            }
            $pubData[] = [
                'time'  => $ins['pubtime'],
                'succ'  => $push
            ];
            Db::name("sys_message")->where('id',$id)->update(['more'=>json_encode($pubData)]);
        }

        $this->success($msg."！", url("message/add"));
    }

    /**
     * 编辑系统消息
     */
    public function edit()
    {
        $id = input('param.id', 0, 'intval');
        $message = Db::name('sys_message')->where('id',$id)->find();
        $this->assign('message', $message);
        return $this->fetch();
    }

    /**
     * 编辑系统消息提交
     */
    public function editPost()
    {
        $validate = new Validate([
            'title'              => 'require',
            'content'              => 'require',
        ]);

        $validate->message([
            'title.require'             => '请填写标题',
            'content.require'           => '请填写内容',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $query =  Db::name("sys_message");

        $oldData = $query->where('id',$data['id'])->find();
        if (!$oldData)
        {
            $this->error("您的操作有误");
        }
        if ($oldData['title'] == $data['title']
            && $oldData['content'] == $data['content']
            && $oldData['status'] == $data['status'])
        {
            $this->error("您没有修改");
        }

        $result = Db::name("sys_message")->update($data);
        if ($result === false) {
            $this->error("保存失败");
        }
        $this->success("保存成功");
    }

    /**
     * 文章系统消息
     */
    public function delete()
    {
        $id = $this->request->param("id");
        $result = Db::name("sys_message")->where('id',$id)->delete();
        if ($result)
        {
            $this->success("删除成功");
        }
        $this->error("删除失败");
    }

    /**
     * 系统消息推送
     */
    public function publish()
    {
        $id = $this->request->param("id");
        $message = Db::name('sys_message')->where('id',$id)->find();
        if (!$message)
        {
            $this->error("您的操作有误");
        }
        if ($message['status'] != 1)
        {
            $this->error("消息已隐藏");
        }
        $sendData = [
            'title'     => $message['title'],
            'msg'       => $message['content'],
            'type'      => 1
        ];
        $push = hook_one("push_msg", $sendData);
        if ($push)
        {
            //更新推送时间
            $re = Db::name('sys_message')->where('id',$id)->update(['pubtime'=>time()]);

            $this->success("推送成功");
        }
        else
        {
            $this->error("推送失败");
        }
    }
}
