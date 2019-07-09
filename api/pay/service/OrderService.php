<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------
namespace api\pay\service;

use api\common\model\CommonModel;
use api\model\PayPaymentModel;
use think\Db;
use think\Exception;
use think\Request;
use think\Log;

class OrderService
{
    private $ratio = 10;

    public function order($user,$data)
    {
        $reOrder = PayPaymentModel::where(['user_id'=>$user['id']])->where('status',0)->where('type',$data['type'])->where('pay_mode',$data['mode'])->where('money',$data['money'])->order('id desc')->find();

        //判断充值类型
        $coin = 0;
        if($data['type'] != PayPaymentModel::TYPE_VIP){
            $data['type'] = PayPaymentModel::TYPE_BALANCE;
            $coin = $data['money'] * $this->ratio;
        }

        $now = time();
        $sn = $reOrder['sn'];

        $data = [
            'user_id'           => $user['id'],
            'type'              => $data['type'],
            'pay_mode'          => $data['mode'],
            'money'             => $data['money'],
            'coin'              => $coin,
            'channel_id'        => $user['channel_id'],
            'channel_statis'    => $user['channel_id'] > 0 ? 1 : 0,//有推广信息的，默认计入统计
            'ip'                => get_client_ip(),
            'add_time'          => $now
        ];
        //订单信息入库
        $res = PayPaymentModel::create($data);
        $reOrder = $res;


        return $reOrder;
    }
}
