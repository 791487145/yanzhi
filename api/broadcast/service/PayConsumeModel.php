<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------
namespace api\broadcast\service;

use api\common\model\CommonModel;

use think\Db;
use think\Exception;

class PayConsumeModel extends CommonModel
{
    protected $name = "portal_post";

    /**
     * @param $order    订单信息
     * 'user_id'            //消费用户ID
     * 'anchor_id'          //主播用户ID
     * 'room_id'            //直播间ID
     * 'sn'                 //订单号
     * 'type'               //消费类型，1送礼物，2私播，3视频聊天，4弹幕，5发消息
     * 'coin'               //消费平台币
     * 'add_time'           //消费时间
     * 'ip'                 //消费时的IP
     * 'gift_id'            //礼物ID，不是礼物的为0
     * 'gift_num'           //礼物数量//视频通话时长
     * @param $anchor   主播信息
     * @param $balance  用户当前余额
     * @return mixed    用户消费后的余额
     */
    public static function consume($order, $anchor, $balance)
    {
        $msg = "";
        try {
            $order['guide_id']               = $anchor['guide_id'];                                  //主播所在工会ID
            switch ($order['type']) {
                case 1://礼物，按照一种类型提成
                    $order['retio']               = $anchor['ratio_gift'];
                    $order['guide_retio']        = $anchor['guide_gift'];
                    break;
                case 2://私播、视频通话按照另一种类型提成
                case 3:
                    $order['retio']               = $anchor['ratio'];
                    $order['guide_retio']        = $anchor['ratio_guide'];
                    break;
                default://4、5类型主播和工会无收入
                    $order['retio']                   = 100;                                                   //平台提成比例
                    $order['guide_retio']            = 0;                                                     //工会提成比例
            }
            $order['coin_system']            = ceil ( $order['coin'] * $order['retio'] / 100 );     //平台提成平台币
            $money = $order['coin'] - $order['coin_system'];                                          //工会+主播的提成--临时数值
            $order['coin_guide']             = ceil( $money * $order['guide_retio'] / 100 );        //工会提成平台币
            $order['coin_anchor']            = $money - $order['coin_guide'];                        //主播收入

            $msg .= "订单开始--";
            //订单入库
            $orderId = Db::name('pay_consume')->insertGetId($order);
            $msg .= "OK：$orderId;";

            //==========收益表数据 start==============
            $msg .= "收益开始--";
            if ($order['coin_anchor'] > 0) {//主播私播收益
                $profit = [
                    'user_id'       => $order['anchor_id'],
                    'table_name'    => 'pay_consume',
                    'table_id'      => $orderId,
                    'type'          => 2,
                    'coin'          => $order['coin_anchor'],//收益表中及那个人民币转换为平台币
                    'add_time'       => $order['add_time']
                ];
                Db::name('pay_profit')->insert($profit);
            }
            $msg .= "主播OK;";
            if($order['coin_guide'] > 0) {//工会会长收益
                $profit = [
                    'user_id'       => $order['guide_id'],
                    'table_name'    => 'pay_consume',
                    'table_id'      => $orderId,
                    'type'          => 3,
                    'coin'          => $order['coin_guide'],//收益表中及那个人民币转换为平台币
                    'add_time'       => $order['add_time']
                ];
                Db::name('pay_profit')->insert($profit);
            }
            $msg .= "会长OK;";
            //==========收益表数据 end==============

            //更新消费用户余额
            $msg .= "余额开始--";
            $balance = $balance - $order['coin'];
            Db::name('user')->where(['id'=>$order['user_id']])->update(['balance'=>$balance]);
            $msg .= "OK：$balance;";

            //直播礼物、私播时，更新房间的 用户消费信息,更新主播-用户消费总金额信息
            if ( in_array( $order['type'] , [ 1 , 2 ] ) && $order['room_id'] > 0 ) {
                $msg .= "直播间开始--";
                Db::name('live_room')->where('id',$order['room_id'])->setInc('total_coin',$order['coin']);

                $field = ($order['gift_id'] == 0 ? 'single_total' : 'gift_total');
                Db::name('live_user')->where('anchor_id',$order['anchor_id'])->where('user_id',$order['user_id'])->setInc($field,$order['coin']);
                $msg .= "OK;";
            }

            Db::commit();

            return [
                'ret'       =>true,
                'message'   => '消费成功',
                'balance'   => $balance
            ];

        } catch (\Exception $e) {
            Db::rollback();
//            var_dump($e);
            return [
                'ret'       => false,
                'message'   => $msg.'消费失败',
                'exception' => $e->getTraceAsString()
            ];
        }
    }
}
