<?php
// +----------------------------------------------------------------------
// | Author: Lixue
// +----------------------------------------------------------------------
namespace api\pay\controller;

use api\model\ChannelModel;
use api\model\PayPaymentModel;
use api\pay\service\PayBeiweiService;
use api\pay\service\WeChatPayService;
use think\Db;
use cmf\controller\RestBaseController;
use think\Log;
use Omnipay\Omnipay;

class PublicController extends RestBaseController
{
    private $ratio = 10;

    public function index()
    {
        $this->success("支付回调");
    }

    public function redirect()
    {
        $data = $this->request->param();

        $check = hook_one("check_pay_sign", $data);
        if (!$check['succ']){
            $this->error($check['message']);
        }

        //判断订单状态
        $order = Db::name('pay_payment')->where(['sn'=>$data['out_trade_no']])->find();
        if (!$order){
            $this->error("订单不存在");
        }
        if ($check['money'] != $order['money']){
            $this->error("订单金额错误");
        }

        if ($order['status'] == 0)//未支付订单
        {
            $update = [
                'status'    => 1,
                'pay_time'  => time(),
                'pay_sn'    => $data['trade_no'],
                'more'      => json_encode($data)
            ];
            $this->updateData($order , $update);
        }

        if ($check['url'] != '')
        {
            header("Location:" . url($check['url']));
        }
        $this->success("已支付成功");
    }


    public function notify()
    {
        $data = file_get_contents("php://input");
        $arr = explode("&",$data);
        $data = [];
        foreach ($arr as $key => $value) {
            $arrs = explode("=",$value);
            $data[$arrs[0]]=$arrs[1];
        }

        $check = hook_one("check_pay_sign", $data);
        if (!$check['succ']){
            exit("fail");
        }
        //判断订单状态
        $order = Db::name('pay_payment')->where(['sn'=>$data['out_trade_no']])->find();
        if (!$order){
            exit("fail");
        }
        if ($check['money'] != $order['money']){
            exit("fail");
        }

        if ($order['status'] == 0)//未支付订单
        {
            $update = [
                'status'    => 1,
                'pay_time'  => time(),
                'pay_sn'    => $data['trade_no'],
                'more'      => json_encode($data)
            ];
            $this->updateData($order , $update);
        }
        exit("success");
    }

    /*
     * 北纬支付回调
     */
    public function beiwei_notify(PayBeiweiService $payBeiweiService)
    {
        $returnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 支付流水号
            "returncode" => $_REQUEST["returncode"],
        );
        Log::alert('returnArray_res'.print_r($returnArray,true));
        $md5key = $payBeiweiService->Md5key;
        ksort($returnArray);
        reset($returnArray);
        $sign = $payBeiweiService->sign($returnArray,$md5key);
        Log::alert('sign_res'.print_r($sign,true));
        if ($_REQUEST["returncode"] == "00") {

            //判断订单状态
            $order = PayPaymentModel::where(['sn'=>$returnArray['orderid']])->find();
            if (!$order){
                exit("fail");
            }
            if ($returnArray['amount'] != $order['money']){
                exit("fail");
            }

            if ($order['status'] == 0)//未支付订单
            {
                $update = [
                    'status'    => 1,
                    'pay_time'  => time(),
                    'pay_sn'    => $returnArray['transaction_id'],
                    'more'      => json_encode($returnArray)
                ];
                $this->updateOrder($order , $update);
            }
            exit("success");
        }
    }

    /**
     * 北纬支付成功后跳转
     * @param PayBeiweiService $payBeiweiService
     */
    public function beiwei_page(PayBeiweiService $payBeiweiService)
    {
        $returnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 支付流水号
            "returncode" => $_REQUEST["returncode"],
        );

        $md5key = $payBeiweiService->Md5key;
        ksort($returnArray);
        reset($returnArray);
        $sign = $payBeiweiService->sign($returnArray,$md5key);

        if ($_REQUEST["returncode"] == "00") {
            exit("success");
        }
    }

    /**
     * 微信支付回调
     * @param WeChatPayService $weChatPayService
     */
    public function wechat_notify(WeChatPayService $weChatPayService)
    {
        $res = $weChatPayService->notify();
        //判断订单状态
        $order = PayPaymentModel::where(['sn'=> $res['out_trade_no']])->find();
        Log::alert('$order_'.print_r($order,true));
        if (!$order){
            exit("fail");
        }
        if (($res['total_fee']/100) != $order['money']){
            exit("fail");
        }
        Log::alert('jindu_1');
        if ($order['status'] == 0)//未支付订单
        {
            Log::alert('jindu_2');
            $update = [
                'status'    => 1,
                'pay_time'  => time(),
                'pay_sn'    => $res['transaction_id'],
                'more'      => json_encode($res)
            ];
            $this->updateOrder($order,$update);
        }
        Log::alert('jindu_3');
        exit("success");
    }


    /***********************************************************************************************************************/


    /**
     * 支付成功后，更新数据(北纬)
     * @param $order    原订单信息
     * @param $update   订单更新信息
     * @throws \think\Exception
     */
    private function updateOrder($order , $update) {
        $user = Db::name('user')->where(['id'=>$order['user_id']])->find();//订单用户信息
        $update['user_times'] = $user['pay_times'] + 1;//用户充值次数

        //获取分成信息
        $update['channel_id']               = 0;                              //无渠道
        $update['channel_parent']           = 0;                              //无上级渠道
        $update['ratio']                     = 100;                            //平台提成100%
        $update['ratio_parent']             = 0;                              //无上级提成
        $update['money_system']             = $order['money'];               //默认全部计入平台收入金额
        $update['money_parent']             = 0;                              //上级收入金额
        $update['money_channel']            = 0;                              //渠道收入金额
        $update['channel_statis']           = $order['channel_statis'];       //计入渠道统计1没扣量
        if ( $order['channel_statis'] == 1 ) {//需要处理推广统计的，获取提成信息
            $channel = Db::name('channel')->where(['id'=>$user['channel_id']])->find();

            if (!$channel ){//平台用户推广
                $appSetting = cmf_get_option('app_settings');
                $update['channel_id']             = $user['channel_id'];             //渠道ID为邀请人ID
                $update['channel_parent']        = 0;                                  //平台用户推广无上级渠道
                $update['ratio']                  = $order['type'] == 1 ? $appSetting['spread_user_cost'] : $appSetting['spread_user_vip'];  //系统设置的平台提成比例
                $update['ratio_parent']          = 0;                                 //平台用户推广无上级提成
            } else {//渠道推广
                $update['channel_id']            = $user['channel_id'];             //渠道ID
                $update['channel_parent']        = $channel['parent_id'];           //上级渠道ID
                $update['ratio']                  = $order['type'] == 1 ? $channel['ratio'] : $channel['ratio_vip'];                  //渠道的平台提成比例
                $update['ratio_parent']          = $order['type'] == 1 ? $channel['ratio_parent'] : $channel['ratio_vip_parent'];   //渠道的上级提成比例

                if ($channel['effective'] < 100) {//有效订单比例<100,表示有扣量订单
                    $count = PayPaymentModel::where('channel_id',$user['channel_id'])->where('status',PayPaymentModel::PAY_SUCCESS)->count();//获取已有订单数量
                    if ($count){//已有订单
                        if ($count['pay_num'] > 5){//5单以内不扣量，从第6单开始扣量
                            if (($count['sta_num'] + 1) / ($count['pay_num'] + 1) > $channel['effective']) {//增加一单后，如果有效订单占比>渠道设置的比例，则当前订单不计入统计
                                $update['channel_statis'] = 0;
                            }
                        }
                    }
                }
            }
            //处理推广分成金额
            /*if (isset($update['channel_statis']) && $update['channel_statis'] == 1){
                $update['money_system']           = $order['money'] * $update['ratio'] / 100;        //平台收入金额
                $m_tmp = $order['money'] - $update['money_system'];
                $update['money_parent']           = $m_tmp * $update['ratio_parent'] / 100;//上级收入金额
                $update['money_channel']          = $m_tmp - $update['money_parent'];
            }*/
            if (isset($update['channel_statis']) && $update['channel_statis'] == 1){
                $update['money_channel']           = $order['money'] * $update['ratio'] / 100;        //渠道收入金额
                $m_tmp = $order['money'] - $update['money_channel'];
                $update['money_parent']           = $m_tmp * $update['ratio_parent'] / 100;//上级收入金额
                $update['money_system']          = $m_tmp - $update['money_parent'];
            }
        }


            //修改订单状态
            Db::name('pay_payment')->where(['id' => $order['id']])->update($update);

            //计入推广收益的订单，新增收益数据
            if (isset($update['channel_statis']) && $update['channel_statis'] == 1) {
                if ($update['money_channel'] > 0) {
                    Db::name('pay_profit')->insert([
                        'user_id' => $user['id'],
                        'table_name' => 'pay_payment',
                        'table_id' => $order['id'],
                        'type' => 0,
                        'coin' => $update['money_channel'] * $this->ratio,//收益表中及那个人民币转换为平台币
                        'add_time' => $update['pay_time']
                    ]);
                }
                if ($update['money_parent'] > 0) {
                    $channel_parent_id = ChannelModel::where('id',$update['channel_parent'])->value('user_id');

                    Db::name('pay_profit')->insert([
                        'user_id' => $channel_parent_id,
                        'table_name' => 'pay_payment',
                        'table_id' => $order['id'],
                        'type' => 4,
                        'coin' => $update['money_parent'] * $this->ratio,
                        'add_time' => $update['pay_time']
                    ]);
                }
            }

            //更新用户信息
            $userUpdate = ['pay_times' => $update['user_times']];
            switch ($order['type']) {
                case "1"://余额充值
                    $userUpdate['coin'] = $user['coin'] + $order['coin'];
                    $userUpdate['balance'] = $user['balance'] + $order['coin'];
                    break;
                case "2"://VIP
                    $start = time();
                    if ($user['vip'] > $start)//VIP尚未到期，则自动延期
                    {
                        $start = $user['vip'];
                    }
                    $vipSetting = cmf_get_option('vip_settings');

                    $end = 0;
                    foreach ($vipSetting['list'] as $value) {
                        if ($order['money'] == $value['money']) {
                            $end = $start + $value['days'] * 86400;
                        }
                    }
                    if ($end == 0) {
                        $this->error("VIP信息更新失败，请联系客服");
                    }
                    $userUpdate['vip'] = $end;

                    break;
                default:
                    $this->error("类型错误");
            }

            Db::name('user')->where(['id' => $user['id']])->update($userUpdate);

    }

    /**
     * 支付成功后，更新数据
     * @param $order    原订单信息
     * @param $update   订单更新信息
     * @throws \think\Exception
     */
    private function updateData($order , $update) {
        $user = Db::name('user')->where(['id'=>$order['user_id']])->find();//订单用户信息
        $update['user_times'] = $user['pay_times'] + 1;//用户充值次数

        //获取分成信息
        $update['channel_id']               = 0;                              //无渠道
        $update['channel_parent']           = 0;                              //无上级渠道
        $update['ratio']                     = 100;                            //平台提成100%
        $update['ratio_parent']             = 0;                              //无上级提成
        $update['money_system']             = $order['money'];               //默认全部计入平台收入金额
        $update['money_parent']             = 0;                              //上级收入金额
        $update['money_channel']            = 0;                              //渠道收入金额
        $update['channel_statis']           = 0;                              //计入渠道统计0没扣量
        if ( $order['channel_statis'] == 1 ) {//需要处理推广统计的，获取提成信息
            $channel = Db::name('channel')->where(['id'=>$user['channel_id']])->find();

            if (!$channel ){//平台用户推广
                $appSetting = cmf_get_option('app_settings');
                $update['channel_id']             = $user['channel_id'];             //渠道ID为邀请人ID
                $update['channel_parent']        = 0;                                  //平台用户推广无上级渠道
                $update['ratio']                  = $order['type'] == 1 ? $appSetting['spread_user_cost'] : $appSetting['spread_user_vip'];  //系统设置的平台提成比例
                $update['ratio_parent']          = 0;                                 //平台用户推广无上级提成
            } else {//渠道推广
                $update['channel_id']            = $user['channel_id'];             //渠道ID
                $update['channel_parent']        = $channel['parent_id'];           //上级渠道用户ID
                $update['ratio']                  = $order['type'] == 1 ? $channel['ratio'] : $channel['ratio_vip'];                  //渠道的平台提成比例
                $update['ratio_parent']          = $order['type'] == 1 ? $channel['ratio_parent'] : $channel['ratio_vip_parent'];   //渠道的上级提成比例
                dump($channel);
                if ($channel['effective'] < 100) {//有效订单比例<100,表示有扣量订单
                    $count = Db::name("pay_payment")
                        ->field("count(id) pay_num,sum(channel_statis) sta_num")->where('channel_id',$user['channel_id'])->where('status',PayPaymentModel::PAY_SUCCESS)
                        ->group("channel_id")->find();//获取已有订单数量
                    dump($count);
                    if ($count){//已有订单
                        if ($count['pay_num'] > 5){//5单以内不扣量，从第6单开始扣量
                            $update['channel_statis'] = 1;
                            if (($count['sta_num'] + 1) / ($count['pay_num'] + 1) > $channel['effective']) {//增加一单后，如果有效订单占比>渠道设置的比例，则当前订单不计入统计
                                $update['channel_statis'] = 0;
                            }
                        }
                    }
                }
            }
            //处理推广分成金额
            if (isset($update['channel_statis']) && $update['channel_statis'] == 1){
                $update['money_system']           = $order['money'] * $update['ratio'] / 100;        //平台收入金额
                $m_tmp = $order['money'] - $update['money_system'];
                $update['money_parent']           = $m_tmp * $update['ratio_parent'] / 100;//上级收入金额
                $update['money_channel']          = $m_tmp - $update['money_parent'];
            }
        }

        //修改订单状态
        Db::name('pay_payment')->where(['id'=>$order['id']])->update($update);

        //计入推广收益的订单，新增收益数据
        if (isset($update['channel_statis']) && $update['channel_statis'] == 1) {
            if ($update['money_channel'] > 0) {
                Db::name('pay_profit')->insert([
                    'user_id'       => $update['channel_id'],
                    'table_name'    => 'pay_payment',
                    'table_id'      => $order['id'],
                    'type'          => 0,
                    'coin'          => $update['money_channel'] * $this->ratio,//收益表中及那个人民币转换为平台币
                    'addtime'       => $update['pay_time']
                ]);
            }
            if ($update['money_parent'] > 0) {
                Db::name('pay_profit')->insert([
                    'user_id'       => $update['channel_parent'],
                    'table_name'    => 'pay_payment',
                    'table_id'      => $order['id'],
                    'type'          => 4,
                    'coin'          => $update['money_parent'] * $this->ratio,
                    'addtime'       => $update['pay_time']
                ]);
            }
        }

        //更新用户信息
        $userUpdate = ['pay_times'=>$update['user_times']];
        switch ($order['type']){
            case "1"://余额充值
                $userUpdate['coin']     = $user['coin'] + $order['coin'];
                $userUpdate['balance']  = $user['balance'] + $order['coin'];
                break;
            case "2"://VIP
                $start = time();
                if ($user['vip'] > $start)//VIP尚未到期，则自动延期
                {
                    $start = $user['vip'];
                }
                $vipSetting = cmf_get_option('vip_settings');

                $end = 0;
                foreach ($vipSetting['list'] as $value)
                {
                    if ($order['money'] == $value['money']){
                        $end = $start + $value['days'] * 86400;
                    }
                }
                if ($end == 0){
                    $this->error("VIP信息更新失败，请联系客服");
                }
                $userUpdate['vip'] = $end;

                break;
            default:
                $this->error("类型错误");
        }

        Db::name('user')->where(['id'=>$user['id']])->update($userUpdate);
    }



    /**********************************************************************************/


    /**
     * 获取VIP等级
     */
    public function getVipType()
    {
        $list = $vipSetting = cmf_get_option('vip_settings');
        $this->success("获取成功",$list['list']);
    }

    /**
     * 获取提现类型
     */
    public function getExtractionType()
    {
        $list = [
            ['code'=>'wxpay',       'name'=>'微信转账'],
            ['code'=>'alipay',      'name'=>'支付宝转账'],
            ['code'=>'unipay',      'name'=>'银行转账'],
        ];
        $this->success('获取成功',$list);
    }

}
