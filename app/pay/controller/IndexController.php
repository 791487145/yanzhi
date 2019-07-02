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
use cmf\controller\AdminBaseController;

class IndexController extends AdminBaseController
{
    /**
     * 充值管理
     */
    public function payment()
    {
        $sn = $this->request->param('sn');
        $type = $this->request->param('type',0,'intval');
        $mode = $this->request->param('mode',0,'intval');
        $where = [];
        if ($sn != '')
        {
            $where['a.sn']      = ['like',"%$sn%"];
        }
        if ($type > 0)
        {
            $where['a.type']    = $type;
        }
        if ($mode > 0)
        {
            $where['a.pay_mode']    = $mode;
        }
        $result = Db::name('pay_payment')->field('a.*,u.user_nickname')
            ->alias('a')
            ->join('__USER__ u', 'a.user_id = u.id')
            ->where($where)
            ->order('id', 'DESC')
            ->paginate(10);
        $this->assign('list', $result->items());
        $this->assign('page', $result->render());
        return $this->fetch();
    }
    /**
     * 充值订单详情
     * @return mixed
     */
    public function paymentInfo()
    {
        $id = $this->request->param('id',0,'intval');
        $go = $this->request->param('go',0,'intval');

        $order = "id desc";
        $where = ['id'=>$id];
        if ($go == 1)//下一个
        {
            $order = "id asc";
            $where = ['id'=>['>',$id]];
        }
        if ($go == -1)//上一个
        {
            $order = "id desc";
            $where = ['id'=>['<',$id]];
        }

        //订单信息
        $order = Db::name('pay_payment')->where($where)->order($order)->find();
        if (!$order){
            $this->error("订单不存在");
        }
        $this->assign('order', $order);

        $channel = [
            'is_channel'   => 0,
            'parent_id'    => $order['channel_parent'],
        ];
        $isChannel = $hasParent = 0;
        if ($order['status'] == 1 && $order['channel_statis'] == 1)//已支付，且计入渠道统计
        {
            //判断是否渠道订单
            $channelInfo = Db::name('channel')->whereIn('user_id',[$order['channel_id'],$order['channel_parent']])->select();
            foreach ($channelInfo as $v)
            {
                if ($v['user_id'] == $order['channel_id'])//渠道推广用户
                {
                    $channel['is_channel'] = 1;
                    $channel['channel_name'] = $v['name'];
                    $channel['channel_code'] = $v['code'];
                }
                if ($v['user_id'] == $order['channel_parent'])//上级渠道信息
                {
                    $channel['parent_name'] = $v['name'];
                    $channel['parent_code'] = $v['code'];
                }
            }
        }

        //获取用户信息
        $ids[] = $order['user_id'];
        if ($isChannel == 0)
        {
            $ids[] = $order['channel_id'];
        }
        $userInfo = Db::name('user')->whereIn('id',$ids)->select();
        foreach ($userInfo as $v)
        {
            if ($v['id'] == $order['user_id'])
            {
                $this->assign('user', $v);
            }
            if ($v['id'] == $order['channel_id'])
            {
                $channel['channel_name'] = $v['user_nickname'];
                $channel['channel_code'] = $v['mobile'];
            }
        }

        $this->assign('channel', $channel);

        return $this->fetch();
    }
    /**
     * 将充值订单计入推广数据
     */
    public function paymentChannel()
    {
        $id = $this->request->param('id',0,'intval');
        $order = Db::name('pay_payment')->where(['id'=>$id])->find();
        if (!$order)
        {
            $this->error("订单信息错误");
        }
        $re = Db::name('pay_payment')->where(['id'=>$id])->update(['channel_statis'=>1]);
        if ($re)
        {
            $now = time();
            $data[] = [
                'user_id'       => $order['channel_id'],
                'table_name'    => 'pay_payment',
                'table_id'      => $order['id'],
                'type'           => 0,
                'coin'           => $order['money_channel'] * 10,
                'add_time'       => $now,
            ];
            if ($order['channel_parent'] > 0 && $order['money_parent'] > 0)
            {
                $data[] = [
                    'user_id'       => $order['channel_parent'],
                    'table_name'    => 'pay_payment',
                    'table_id'      => $order['id'],
                    'type'           => 0,
                    'coin'           => $order['money_parent'] * 10,
                    'add_time'       => $now,
                ];
            }
            $res = Db::name('pay_profit')->insertAll($data);
            if ($res)
            {
                $this->success("操作成功");
            }
        }
        $this->error("操作失败");
    }

    /**
     * 消费管理
     */
    public function consume()
    {
        $sn = $this->request->param('sn');
        $type = $this->request->param('type',0,'intval');
        $where = [];
        if ($sn != '')
        {
            $where['a.sn']      = ['like',"%$sn%"];
        }
        if ($type > 0)
        {
            $where['a.type']    = $type;
        }
        $result = Db::name('pay_consume')->field('a.*,u.user_nickname')
            ->alias('a')
            ->join('__USER__ u', 'a.user_id = u.id')
            ->where($where)
            ->order('id', 'DESC')
            ->paginate(10);
        $this->assign('list', $result->items());
        $this->assign('page', $result->render());
        return $this->fetch();
    }
    /**
     * 消费订单详情
     * @return mixed
     */
    public function consumeInfo()
    {
        $id = $this->request->param('id',0,'intval');
        $go = $this->request->param('go',0,'intval');

        $order = "id desc";
        $where = ['id'=>$id];
        if ($go == 1)//下一个
        {
            $order = "id asc";
            $where = ['id'=>['>',$id]];
        }
        if ($go == -1)//上一个
        {
            $order = "id desc";
            $where = ['id'=>['<',$id]];
        }

        //订单信息
        $order = Db::name('pay_consume')->where($where)->order($order)->find();
        if (!$order){
            $this->error("订单不存在");
        }

        //推广信息
        $channel = [
            'is_channel'   => 0,
            'parent_id'    => $order['channel_parent'],
        ];
        $isChannel = $hasParent = 0;
        if ($order['channel_statis'] == 1)//已支付，且计入渠道统计
        {
            //判断是否渠道订单
            $channelInfo = Db::name('channel')->whereIn('user_id',[$order['channel_id'],$order['channel_parent']])->select();
            foreach ($channelInfo as $v)
            {
                if ($v['user_id'] == $order['channel_id'])//渠道推广用户
                {
                    $channel['is_channel'] = 1;
                    $channel['channel_name'] = $v['name'];
                    $channel['channel_code'] = $v['code'];
                }
                if ($v['user_id'] == $order['channel_parent'])//上级渠道信息
                {
                    $channel['parent_name'] = $v['name'];
                    $channel['parent_code'] = $v['code'];
                }
            }
        }

        //主播信息
        $anchor = Db::name('live_anchor')->where(['user_id'=>$order['anchor_id']])->find();
        //工会信息
        if ($order['guide_id'] > 0)
        {
            $guide = Db::name('guide')->where(['user_id'=>$order['guide_id']])->find();
            $this->assign('guide', $guide);
        }
        $anchorLevel= Db::name('live_level')->where(['id'=>$anchor['level']])->find();
        $anchor['level_name']   = $anchorLevel['name'];

        if ($order['gift_id'] == 0)
        {
            $order['gift_name'] = "单价";
        }
        else
        {
            $gift = Db::name('gift')->where(['id'=>$order['gift_id']])->find();
            $order['gift_name'] = $gift['name'];
        }

        //获取用户信息
        $ids[] = $order['user_id'];
        $ids[] = $order['anchor_id'];
        if ($isChannel == 0)
        {
            $ids[] = $order['channel_id'];
        }
        $userInfo = Db::name('user')->whereIn('id',$ids)->select();
        foreach ($userInfo as $v)
        {
            if ($v['id'] == $order['user_id'])
            {
                $this->assign('user', $v);
            }
            if ($v['id'] == $order['anchor_id'])
            {
                $anchor['name']     = $v['user_nickname'];
            }
            if ($v['id'] == $order['channel_id'])
            {
                $channel['channel_name'] = $v['user_nickname'];
                $channel['channel_code'] = $v['mobile'];
            }
        }
        $this->assign('order', $order);
        $this->assign('anchor', $anchor);
        $this->assign('channel', $channel);

        return $this->fetch();
    }
    /**
     * 将消费订单计入推广数据
     */
    public function consumeChannel()
    {
        $id = $this->request->param('id',0,'intval');
        $order = Db::name('pay_consume')->where(['id'=>$id])->find();
        if (!$order)
        {
            $this->error("订单信息错误");
        }
        $re = Db::name('pay_consume')->where(['id'=>$id])->update(['channel_statis'=>1]);
        if ($re)
        {
            $now = time();
            $data[] = [
                'user_id'       => $order['channel_id'],
                'table_name'    => 'pay_payment',
                'table_id'      => $order['id'],
                'type'           => 0,
                'coin'           => $order['money_channel'] * 10,
                'add_time'       => $now,
            ];
            if ($order['channel_parent'] > 0 && $order['money_parent'] > 0)
            {
                $data[] = [
                    'user_id'       => $order['channel_parent'],
                    'table_name'    => 'pay_payment',
                    'table_id'      => $order['id'],
                    'type'           => 0,
                    'coin'           => $order['money_parent'] * 10,
                    'add_time'       => $now,
                ];
            }
            $res = Db::name('pay_profit')->insertAll($data);
            if ($res)
            {
                $this->success("操作成功");
            }
        }
        $this->error("操作失败");
    }

    /**
     * 收益管理
     */
    public function profit()
    {
        $sn = $this->request->param('sn');
        $type = $this->request->param('type',-1,'intval');
        $this->assign('type', $type);
        $where = [];
        if ($sn != '')
        {
            $where['p.sn|c.sn']      = ['like',"%$sn%"];
        }
        if ($type >= 0)
        {
            $where['f.type']    = $type;
        }
        $result = Db::name('pay_profit')->field('f.*,p.sn psn,c.sn csn')
            ->alias('f')
            ->join('pay_payment p', "p.id=f.table_id and f.table_name='pay_payment'",'left')
            ->join('pay_consume c', "c.id=f.table_id and f.table_name='pay_consume'",'left')
            ->where($where)
            ->order('id', 'DESC')
            ->paginate(10);

        $this->assign('list', $result->items());
        $this->assign('page', $result->render());
        return $this->fetch();
    }
    /**
     * 受益详情
     */
    public function profitInfo()
    {
        $id = $this->request->param('id',0,'intval');
        $go = $this->request->param('go',0,'intval');

        $order = "id desc";
        $where = ['id'=>$id];
        if ($go == 1)//下一个
        {
            $order = "id asc";
            $where = ['id'=>['>',$id]];
        }
        if ($go == -1)//上一个
        {
            $order = "id desc";
            $where = ['id'=>['<',$id]];
        }

        //收益信息
        $order = Db::name('pay_profit')->where($where)->order($order)->find();
        if (!$order){
            $this->error("收益记录不存在");
        }

        //关联表信息
        $about = Db::name($order['table_name'])->where(['id'=>$order['table_id']])->find();
        if (!$about){
            $this->error("关联订单不存在");
        }

        if ($order['user_id'] == $about['channel_id'])//推广员或渠道
        {
            //判断是否为渠道
            $channel = Db::name('channel')->where(['user_id'=>$order['user_id']])->find();
            if (!$channel)//推广员
            {
                //获取推广员信息
                $userInfo = Db::name('user')->where(['id'=>$order['user_id']])->find();
                $order['utype'] = "推广员";
                $order['name']  = $userInfo['user_nickname'];
                $order['money'] = $order['table_name'] == $about['money_channel'];
            }
            else//渠道
            {
                $order['utype'] = "渠道推广";
                $order['name']  = $channel['name'];
            }
        }
        elseif ($order['user_id'] == $about['channel_parent'])//上级推广渠道
        {
            //判断渠道是否存在
            $channel = Db::name('channel')->where(['user_id'=>$order['user_id']])->find();
            if (!$channel)
            {
                $this->error("渠道信息错误");
            }
            $order['utype'] = "上级渠道";
            $order['name']  = $channel['name'];
        }
        elseif ($order['user_id'] == $about['guide_id'])//消费订单-工会
        {
            //判断工会是否存在
            $guide = Db::name('guide')->where(['user_id'=>$order['user_id']])->find();
            if (!$guide)
            {
                $this->error("工会信息错误");
                $order['utype'] = "工会提成";
                $order['name']  = $guide['name'];
            }
        }
        elseif ($order['user_id'] == $about['anchor_id'])//消费订单-主播
        {
            //主播信息
            $userInfo = Db::name('user')->where(['id'=>$order['user_id']])->find();
            $order['utype'] = "主播提成";
            $order['name']  = $userInfo['user_nickname'];

        }

        $this->assign('about', $about);
        $this->assign('order', $order);
        return $this->fetch();
    }
}