<?php
// +----------------------------------------------------------------------
// | 虎虎科技
// +----------------------------------------------------------------------
// | 订单管理
// +----------------------------------------------------------------------
// | Author: 李雪
// +----------------------------------------------------------------------
namespace app\pay\controller;

use think\Db;
use think\Validate;
use cmf\controller\AdminBaseController;

class GiftController extends AdminBaseController
{
    /**
     * 礼物管理
     */
    public function index(){
        $result = Db::name('gift')->order('mark asc')->select();
        $this->assign('list', $result);
        return $this->fetch();
    }

    /**
     * 添加礼物页面
     * @return mixed
     */
    public function add() {
        return $this->fetch();
    }

    /**
     * 添加礼物提交
     */
    public function addPost() {
        $validate = new Validate([
            'name'              => 'require',
            'pic'               => 'require',
            'coin'              => 'require|min:1',
            'mark'              => 'require|min:1|max:99',
        ]);

        $validate->message([
            'name.require'              => '请填写礼物名称',
            'pic.require'               => '请上传礼物图片',
            'coin.require'              => '请填写礼物价格',
            'live_reward.min'           => '礼物价格不能低于1',
            'mark.require'              => '请填写礼物排序',
            'mark.max'                  => '礼物排序不能超过99',
            'mark.min'                  => '礼物排序不能低于1',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $data['status'] = 1;//默认为启用状态
        if (strpos($data['pic'],'http://') === false && strpos($data['pic'],'/upload') === false) {
            $data['pic'] = '/upload/'.$data['pic'];
        }

        $result    = Db::name("gift")->insert($data);
        if ($result === false) {
            $this->error("添加失败");
        }

        $this->success("添加成功！", url("pay/gift/add"));
    }

    /**
     * 编辑礼物页面
     * @return mixed
     */
    public function edit() {
        $id = input('id', 0, 'intval');
        $data = Db::name("gift")->where('id',$id)->find();
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 编辑礼物提交
     */
    public function editPost() {
        $validate = new Validate([
            'name'              => 'require',
            'pic'               => 'require',
            'coin'              => 'require|min:1',
            'mark'              => 'require|min:1|max:99',
        ]);

        $validate->message([
            'name.require'              => '请填写礼物名称',
            'pic.require'               => '请上传礼物图片',
            'coin.require'              => '请填写礼物价格',
            'live_reward.min'           => '礼物价格不能低于1',
            'mark.require'              => '请填写礼物排序',
            'mark.max'                  => '礼物排序不能超过99',
            'mark.min'                  => '礼物排序不能低于1',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if (strpos($data['pic'],'http://') === false && strpos($data['pic'],'/upload') === false) {
            $data['pic'] = '/upload/'.$data['pic'];
        }

        $result    = Db::name("gift")->update($data);
        if ($result === false) {
            $this->error("编辑失败");
        }

        $this->success("编辑成功！", url("pay/gift/edit",array('id'=>$data['id'])));
    }

    /**
     * 变更状态
     * @throws \think\Exception
     */
    public function status() {
        $id = input('id', 0, 'intval');
        $status = input('status', 0, 'intval');
        $result = Db::name("gift")->where('id',$id)->update(['status'=>$status]);
        if ($result === false) {
            $this->error("操作失败");
        }
        $this->success("操作成功！");
    }

    public function del() {
        $id = input('id', 0, 'intval');
        $result = Db::name("gift")->where('id',$id)->delete();
        if ($result === false) {
            $this->error("删除失败");
        }
        $this->success("删除成功！");
    }
}