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

class ChannelController extends AdminBaseController
{
    /**
     * 渠道管理
     * @return mixed
     */
    public function index()
    {
        $where = $keywordComplex = [];
        $request = input('request.');
        if (!empty($request['status'])) {
            $where['status'] = intval($request['status']);
        }

        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $keywordComplex['code|name|cdr|mobile|qq|weixin']    = ['like', "%$keyword%"];
        }
        $query = Db::name('channel');
        $arr = $query->field('user_id,name')->select();
        $hash = [0=>'无'];
        foreach ($arr as $value)
        {
            $hash[$value['user_id']] = $value['name'];
        }
        $this->assign('hash', $hash);
        if (count($where) > 0)
        {
            $query->where($where);
        }
        if (count($keywordComplex) > 0)
        {
            $query->whereOr($keywordComplex);
        }

        $list = $query->order("id DESC")->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 添加渠道页面
     */
    public function add()
    {
        $appSetting = cmf_get_option('app_settings');
        $this->assign($appSetting);
        return $this->fetch();
    }

    /**
     * 添加渠道提交保存
     */
    public function addPost()
    {
        $validate = new Validate([
            'u.user_login'      => 'require',
            'u.user_pass'       => 'require|min:6|max:20',
            'c.name'            => 'require',
            'c.cdr'             => 'require',
            'c.pay_name'       => 'require',
            'c.pay_type'       => 'require',
            'c.pay_account'    => 'require',
            'c.ratio_vip'      => 'require|min:0|max:100',
            'c.ratio'          => 'require|min:0|max:100',
            'c.ratio_sub'      => 'require|min:0|max:100',
            'c.effective'      => 'require|min:0|max:100',
        ]);
        $validate->message([
            'u.user_login.require'    => '请填写登录账号',
            'u.user_pass.require'     => '请填写登录密码',
            'u.user_pass.max'         => '登录密码长度不能超过20',
            'u.user_pass.min'         => '登录密码长度不能小于6',
            'c.name.require'           => '请填写渠道名称',
            'c.cdr.require'            => '请填写联系人姓名',
            'c.pay_name.require'       => '请填写收款人姓名',
            'c.pay_type.require'       => '请填写收款方式',
            'c.pay_account.require'    => '请填写收款账户',
            'c.ratio_vip.require'       => '请填写VIP充值平台提成',
            'c.ratio_vip.max'           => 'VIP充值平台提成不能超过100%',
            'c.ratio_vip.min'           => 'VIP充值平台提成不能低于0%',
            'c.ratio.require'           => '请填写消费平台提成',
            'c.ratio.max'               => '消费平台提成不能超过100%',
            'c.ratio.min'               => '消费平台提成不能低于0%',
            'c.ratio_sub.require'      => '请填写对子渠道的提成',
            'c.ratio_sub.max'          => '对子渠道的提成不能超过100%',
            'c.ratio_sub.min'          => '对子渠道的提成不能低于0%',
            'c.effective.require'      => '请填写有效订单比例',
            'c.effective.max'          => '有效订单比例不能超过100%',
            'c.effective.min'          => '有效订单比例不能低于0%',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if ($data['c']['mobile'] == '' && $data['c']['qq'] == '' && $data['c']['weixin'] == '')
        {
            $this->error("请填写联系方式");
        }

        $user = $data['u'];
        $channel = $data['c'];

        //添加用户信息
        $user['user_pass']  = cmf_password($user['user_pass']);
        $user['mobile']     = $channel['mobile'];
        $user['user_type']  = 3;

        //判断账号是否存在
        $userQuery = Db::name('user');
        $checkUser = $userQuery->where('user_login',$user['user_login'])->find();
        if ($checkUser)//新用户，判断登录账号重复
        {
            $this->error("登录账号已存在");
        }
        //判断渠道信息是否存在
        $channelQuery = Db::name('channel');
        $whereChannel = ['name'=>$channel['name']];
        if ($channel['mobile'] != '')$whereChannel['mobile'] = $channel['mobile'];
        if ($channel['qq'] != '')$whereChannel['qq'] = $channel['qq'];
        if ($channel['weixin'] != '')$whereChannel['weixin'] = $channel['weixin'];
        $checkChannel = $channelQuery->whereOr($whereChannel)->find();
        if ($checkChannel)
        {
            if ($checkChannel['name'] == $channel['name'])$this->error("渠道名称已存在");
            if ($checkChannel['mobile'] != '' && $checkChannel['mobile'] == $channel['mobile'])$this->error("手机号码已存在");
            if ($checkChannel['qq'] != '' && $checkChannel['qq'] == $channel['qq'])$this->error("QQ号码已存在");
            if ($checkChannel['weixin'] != '' && $checkChannel['weixin'] == $channel['weixin'])$this->error("微信账号已存在");
        }
        //开启事务
        Db::startTrans();

        $userId = $userQuery->insertGetId($user);
        if (!$userId)
        {
            $this->error("用户添加失败");
        }
        $channel['user_id'] = $userId;
        $channel['status']  = 1;

        $re = $channelQuery->insert($channel);
        if (!$re) {
            //事务回滚
            Db::rollback();
            $this->error("渠道添加失败");
        }
        //事务提交
        Db::commit();

        $this->success("添加成功！", url("channel/add"));
    }

    /**
     * 编辑渠道页面
     */
    public function edit()
    {
        $id        = $this->request->param('id', 0, 'intval');
        $channel = Db::name('channel')->where('id',$id)->find();
        if (!$channel)
        {
            $this->error("渠道不存在");
        }
        $user = Db::name("user")->where('id',$channel['user_id'])->where('user_type',3)->find();
        if (!$user)
        {
            $this->error("用户不存在");
        }
        $this->assign('c', $channel);
        $this->assign('u', $user);
        return $this->fetch();
    }

    /**
     * 编辑渠道提交保存
     */
    public function editPost()
    {
        $data = $this->request->post();
        $user = $data['u'];
        $channel = $data['c'];

        $arrValidate = [
            'c.cdr'             => 'require',
            'c.pay_name'       => 'require',
            'c.pay_type'       => 'require',
            'c.pay_account'    => 'require',
            'c.ratio_vip'      => 'require|min:0|max:100',
            'c.ratio'          => 'require|min:0|max:100',
            'c.ratio_sub'      => 'require|min:0|max:100',
            'c.effective'      => 'require|min:0|max:100',
        ];
        $arrMessage = [
            'c.cdr.require'            => '请填写联系人姓名',
            'c.pay_name.require'       => '请填写收款人姓名',
            'c.pay_type.require'       => '请填写收款方式',
            'c.pay_account.require'    => '请填写收款账户',
            'c.ratio_vip.require'       => '请填写VIP充值平台提成',
            'c.ratio_vip.max'           => 'VIP充值平台提成不能超过100%',
            'c.ratio_vip.min'           => 'VIP充值平台提成不能低于0%',
            'c.ratio.require'           => '请填写消费平台提成',
            'c.ratio.max'               => '消费平台提成不能超过100%',
            'c.ratio.min'               => '消费平台提成不能低于0%',
            'c.ratio_sub.require'      => '请填写对子渠道的提成',
            'c.ratio_sub.max'          => '对子渠道的提成不能超过100%',
            'c.ratio_sub.min'          => '对子渠道的提成不能低于0%',
            'c.effective.require'      => '请填写有效订单比例',
            'c.effective.max'          => '有效订单比例不能超过100%',
            'c.effective.min'          => '有效订单比例不能低于0%',
        ];
        if ($user['user_pass'] != '')
        {
            $arrValidate['u.user_pass'] = 'min:6|max:20';
            $arrMessage['u.user_pass.max']  = '登录密码长度不能超过20';
            $arrMessage['u.user_pass.min']  = '登录密码长度不能小于6';
        }
        $validate = new Validate($arrValidate);
        $validate->message($arrMessage);

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if ($data['c']['mobile'] == '' && $data['c']['qq'] == '' && $data['c']['weixin'] == '')
        {
            $this->error("请填写联系方式");
        }

        //修改密码
        $reUser = true;
        if ($user['user_pass'] != '')
        {
            $user['user_pass'] = cmf_password($user['user_pass']);
            $reUser = Db::name('user')->update($user);
        }
        //判断联系方式是否已存在
        $channelQuery = Db::name('channel');

        $whereChannel = [];
        if ($channel['mobile'] != '')$whereChannel['mobile'] = $channel['mobile'];
        if ($channel['qq'] != '')$whereChannel['qq'] = $channel['qq'];
        if ($channel['weixin'] != '')$whereChannel['weixin'] = $channel['weixin'];
        $checkChannel = $channelQuery->whereOr($whereChannel)->select();
        foreach ($checkChannel as $value)
        {
            if ($value['id'] != $channel['id'])
            {
                if ($channel['mobile'] != '' && $value['mobile'] == $channel['mobile'])$this->error("手机号码已存在");
                if ($channel['qq'] != '' && $value['qq'] == $channel['qq'])$this->error("QQ号码已存在");
                if ($channel['weixin'] != '' && $value['weixin'] == $channel['weixin'])$this->error("微信账号已存在");
            }
        }

        $reChannel = $channelQuery->update($channel);

        if ($reUser === false) {
            $this->error("密码修改失败");
        }
        if ($reChannel === false) {
            $this->error("渠道信息修改失败");
        }

        $this->success("保存成功！", url("channel/edit",array('id'=>$channel['id'])));
    }

    /**
     * 开始/停止合作
     */
    public function status()
    {
        $id        = $this->request->param('id', 0, 'intval');
        $channel = Db::name('channel')->where('id',$id)->find();
        if (!$channel)
        {
            $this->error("您的操作有误");
        }
        if ($channel['status'] == 1)
        {
            $channel['status'] = 3;
            $msg = "解约";
        }
        else
        {
            $channel['status'] = 1;
            $msg = "启用";
        }
        $re = Db::name('channel')->update($channel);
        if ($re)
        {
            $this->success($msg."成功");
        }
        else
        {
            $this->error($msg."失败");
        }
    }
}