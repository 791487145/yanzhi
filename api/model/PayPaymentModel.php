<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace api\model;

use think\Model;

class PayPaymentModel extends Model
{
    const PAY_MODE_ALIPAY = 1;//支付宝
    const PAY_MODE_WECHAT_H5 = 2;//微信

    const TYPE_BALANCE = 1;//余额
    const TYPE_VIP = 2;//vip

    public $type_name = array(
        self::TYPE_BALANCE => "充值余额",
        self::TYPE_VIP => "vip充值"
    );

    protected static function init()
    {
        self::beforeInsert(function ($model) {
            $model->sn = self::order_sn();
        });
    }


    /**
     * 创建订单编号
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function order_sn()
    {
        do {
            $sn = date("YmdHis");
            for ($i = 0; $i < 4; $i++)
            {
                $sn .= rand(0,9);
            }
            $data = self::where(['sn'=>$sn])->find();
            $has = $data ? true : false;
        }while($has);
        return $sn;
    }

    /**
     * 充值vip效验钱是否一致或存在或多个
     * @param $money
     * @return int
     */
    public function check_pay_vip_money($money)
    {
        $vipSetting = cmf_get_option('vip_settings');
        $i = 0;
        foreach ($vipSetting['list'] as $value)
        {
            if ($money == $value['money']){
                $i = $i + 1;
            }
        }
        return $i;
    }



}