<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\pay\controller;

use api\model\PayPaymentModel;
use api\pay\service\OrderService;
use api\pay\service\PayBeiweiService;
use api\request\OrderRequest;
use think\Db;
use cmf\controller\RestUserBaseController;
use think\Request;
use think\Log;

class IndexController extends RestUserBaseController
{
    private $ratio = 10;
    private $pay_status = true;

    /**
     * 创建订单
     * http://yanzhi.s1.natapp.cc/api/pay/create
     */
    public function payOrder()
    {
        $user = $this->user;

        $type = $this->request->post('type', 0, 'intval');
        $mode = $this->request->post('mode', 0, 'intval');
        $money = $this->request->post('money', 0, 'intval');
        if ($money == 0)$this->error("请选择充值金额");

        //判断支付方式
        switch ($mode){
            case "1":
                //支付宝;
                break;
            case "2":
                //微信支付
                break;
            default:
                $this->error("请选择支付方式");
        }
        //判断充值类型
        switch ($type){
            case "2"://充值VIP
                $coin = 0;//无平台金币
                break;
            default://默认充值余额
                $type = 1;
                $coin = $money * $this->ratio;
        }

        $now = time();//当前时间戳
        $table = "pay_payment";
        $insert = false;
        $reOrder = Db::name($table)->where(['user_id'=>$user['id']])->where('status',0)->where('type',$type)->where('pay_mode',$mode)->where('money',$money)->order('id desc')->find();
        if ($reOrder['add_time'] > ( $now - 300 )){//订单信息与5分钟内上一单未支付订单信息的相同，则直接使用上一单的订单信息
            $data = $reOrder;
            $sn = $reOrder['sn'];
        } else{//否则生成新的订单
            $insert = true;
            $sn = create_sn($now,$table);
            $data = [
                'user_id'           => $this->userId,
                'sn'                => $sn,
                'type'              => $type,
                'pay_mode'          => $mode,
                'money'             => $money,
                'coin'              => $coin,
                'channel_id'        => $user['channel_id'],
                'channel_statis'    => $user['channel_id'] > 0 ? 1 : 0,//有推广信息的，默认计入统计
                'ip'                => get_client_ip(),
                'add_time'          => $now
            ];
            //订单信息入库
            Db::name('pay_payment')->insert($data);
        }
        //获取第三方支付URL
        $data['device'] = $this->deviceType;
        $payurl = hook_one("get_pay_url", $data);

        $this->success('下单成功',['order_sn'=>$sn,'url'=>$payurl]);
    }


    public function payBeiweiOrder(OrderService $orderService,Request $request,PayBeiweiService $payBeiweiService,PayPaymentModel $paymentModel)
    {
        $user = $this->user;

        $data = $request->only(['type','mode','money']);
        if(!$this->pay_status){
            $data['mode'] = PayPaymentModel::PAY_MODE_WECHAT_H5;
        }

        $result = $this->validate($data,OrderRequest::class);
        Log::alert('validate_res'.print_r($result,true));
        if(!empty($result)){
            $this->error($result);
        }

        $res = $orderService->order($user,$data);
        Log::alert('order_res'.print_r($res,true));
        $result = $payBeiweiService->pay($request,$paymentModel,$res['sn'],$res,$data['mode']);
        Log::alert('pay_res'.print_r($result,true));
        $this->success('下单成功',$result);
    }

    /**
     * 获取充值记录
     */
    public function payList()
    {
        $user = $this->user;
        $pageSize = 10;//每次加载10条记录
        $where = [
            'user_id'=>$this->userId,
            'status'=>1
        ];
        $id = $this->request->post('id', 0, 'intval');
        if ($id > 0)
        {
            $where['id'] = ['LT',$id];
        }
        $list = Db::name('pay_payment')
            ->field('id,sn order_sn,type,pay_mode mode,money,pay_time time')
            ->where($where)
            ->order("id desc")
            ->limit( 0 , $pageSize )
            ->select();
        if (!$list || count($list) == 0)
        {
            $this->error("暂无数据");
        }
        $this->success("获取成功",$list);
    }

    /**
     * 获取消费记录
     */
    public function cosList()
    {
        $user = $this->user;
        $pageSize = 10;//每次加载10条记录
        $where = [
            'user_id'=>$this->userId
        ];
        $id = $this->request->post('id', 0, 'intval');
        if ($id > 0)
        {
            $where['id'] = ['LT',$id];
        }
        $list = Db::name('pay_consume')
            ->field("id,sn order_sn,type,coin,add_time time,case type when 1 then '礼物' when 2 then '私播' when 3 then '聊天' when 4 then '弹幕' else '其他' end as type_name")
            ->where($where)
            ->order("id desc")
            ->limit( 0 , $pageSize )
            ->select();
        if (!$list || count($list) == 0)
        {
            $this->error("暂无数据");
        }
        $this->success("获取成功",$list);
    }

    /**
     * 获取收益记录
     */
    public function profitList()
    {
        $user = $this->user;
        $pageSize = 10;//每次加载10条记录
        $where = [ 'user_id' => $this->userId ];
        $id = $this->request->post('id', 0, 'intval');
        if ($id > 0) {
            $where['id'] = ['LT',$id];
        }
        $list = Db::name('pay_profit')
            ->field("id,type,coin,add_time time,case type when 1 then '礼物' when 2 then '私播' else '其他' end as type_name")//0渠道提成，1主播礼物，2主播私播，3会长提成，4对子渠道提成
            ->where($where)
            ->order("id desc")
            ->limit( $pageSize )
            ->select();
        if ( !$list || count($list) == 0 ) {
            $this->error("暂无数据");
        }
        $this->success("获取成功",$list);
    }

    /**
     * 日收益记录
     */
    public function profitDayList() {
        $user = $this->user;
        $pageSize = 10;//每次加载10条记录
        $where = [ 'user_id' => $this->userId ];
        $date = $this->request->post('date');
        if ($date != '') {
            $where["FROM_UNIXTIME(add_time,'%Y-%m-%d')"] = ['GT',$date];
        }
        $list = Db::name('pay_profit')
            ->field("FROM_UNIXTIME(add_time,'%Y-%m-%d') as pdate,type,sum(coin) as coin,COUNT(id) as num")//0渠道提成，1主播礼物，2主播私播，3会长提成，4对子渠道提成
            ->where($where)
            ->whereIn('type',[1,2])
            ->group("pdate,type")
            ->order("pdate desc,type desc")
            ->limit( $pageSize * 2 )
            ->select();

        if ( !$list || count($list) == 0 || is_null($list[0]['pdate']) ) {
            $this->error("暂无数据");
        }
        $debug = $this->request->post('debug', 0, 'intval');
        if ($debug == 1) {
            var_dump(Db::name('pay_profit')->getLastSql());
        }

        $data = [];
        $pdate = "";
        $i = -1;
        foreach ($list as $value) {
            if ($value['pdate'] != $pdate) {
                $pdate = $value['pdate'];
                $i++;
                $data[$i] = [
                    'date'      => $pdate,
                    'coin'      => 0,
                    'coin1'     => 0,
                    'coin2'     => 0,
                    'num1'      => 0,
                    'num2'      => 0
                ];
            }
            $data[$i]['coin'.$value['type']] += $value['coin'];
            $data[$i]['num'.$value['type']] += $value['num'];
            $data[$i]['coin'] += $value['coin'];
            if ($i >= 9) break;
        }
        $this->success("获取成功",$data);
    }

    /**
     * 提现记录
     */
    public function extractionList()
    {
        $user = $this->user;
        $pageSize = 10;//每次加载10条记录
        $where = [
            'user_id'=>$this->userId,
        ];
        $id = $this->request->post('id', 0, 'intval');
        if ($id > 0)
        {
            $where['id'] = ['LT',$id];
        }
        $list = Db::name('pay_extraction')
            ->field('id,coin,money,add_time time,status,pay_name,pay_type,pay_account')
            ->where($where)
            ->order("id desc")
            ->limit( 0 , $pageSize )
            ->select();
        if (!$list || count($list) == 0)
        {
            $this->error("暂无数据");
        }
        $this->success("获取成功",$list);
    }

    /**
     * 获取钱包余额
     */
    public function package()
    {
        $reData = ['coin'=>$this->user['balance']];
        $coin = Db::name('pay_profit')->field('sum(coin) coin')->group('user_id')->where(['user_id'=>$this->userId])->find();
        if (!$coin)
        {
            $reData['profit'] = 0;
        }
        else
        {
            $reData['profit'] = $coin['coin'];
        }

        $extraction = Db::name('pay_extraction')->field('sum(coin) coin')->group('user_id')->where(['user_id'=>$this->userId,'status'=>['<>',3]])->find();
        if ($extraction)
        {
            $reData['profit'] = $reData['profit'] - $extraction['coin'];
        }

        $this->success("获取成功",$reData);
    }

    /**
     * 提现申请
     */
    public function extraction()
    {
        $coin = $this->request->post('coin', 0, 'intval');
        $base = 100;
        if ( $coin % $base > 0){
            $this->error('提现平台币应为'.$base.'的倍数');
        }
        $minCoin = 1000;
        if ($coin < $minCoin){
            $this->error('最低提现数量为'.$minCoin."平台币");
        }
        $money = $coin / 10;
        //用户当前收益余额
        $profit = 0;
        $coin = Db::name('pay_profit')->field('sum(coin) coin')->group('user_id')->where(['user_id'=>$this->userId])->find();
        if (!$coin){
            $this->error("您还没有收益");
        }
        $profit = $coin['coin'];
        $extraction = Db::name('pay_extraction')->field('sum(coin) coin')->group('user_id')->where(['user_id'=>$this->userId,'status'=>['<>',3]])->find();
        if ($extraction)//已有提现记录时，余额为收益总和-提现总和
        {
            $profit = $profit - $extraction['coin'];
        }
        if ($coin > $profit)
        {
            $this->error("收益余额不足");
        }
        $now = time();
        $sn = $this->createSn($now,'pay_extraction');
        $re = Db::name('pay_extraction')->insert([
            'sn'            => $sn,
            'user_id'       => $this->userId,
            'coin'          => $coin,
            'money'         => $money,
            'add_time'      => $now,
            'status'        => 0,
            'pay_name'      => $this->user['truename'],
            'pay_type'      => $this->user['pay_type'],
            'pay_account'   => $this->user['pay_account'],
        ]);
        if (!$re)
        {
            $this->error("申请失败");
        }
        $this->success("申请成功，请等待审核");
    }
}
