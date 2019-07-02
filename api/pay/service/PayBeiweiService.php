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

class PayBeiweiService
{
    const ALIPAY = 1;
    const WECHAT_H5 = 3;

    protected $ratio = 100;
    protected $pay_memberid = "190645361";//商户ID
    protected $pay_applydate;//订单时间
    protected $pay_orderid;//订单号
    protected $pay_amount;//订单金额
    protected $pay_notifyurl;//服务端返回地址
    protected $pay_callbackurl;//页面跳转返回地址
    public $Md5key = '29u0vw4mvr48c6cnh36kz4pjkx8uibsj';//密钥
    protected $pay_md5sign;
    protected $tjurl ='http://pay.beiweipay.com/Pay_Index.html';//提交地址
    protected $pay_bankcode = array(
        self::ALIPAY => PayPaymentModel::PAY_MODE_ALIPAY,//支付宝
        self::WECHAT_H5 => PayPaymentModel::PAY_MODE_WECHAT_H5//微信h5
    );
    //支付宝
    protected $pay_amount_alipay = array(
        10,20,30,50,100,200
    );
    //微信h5
    protected $pay_amount_wechat_h5 = array(
        20,30,50,100,200,300,500
    );

    public function __construct()
    {
        $request = Request::instance();
        $host = $request->host();
        $this->pay_notifyurl = "http://".$host."/api/pay/public/beiwei_notify";
        $this->pay_callbackurl = "http://".$host."/api/pay/public/beiwei_page";
    }

    public function pay(Request $request,PayPaymentModel $paymentModel,$pay_orderid,$param,$pay_bankcode,$pay_callbackurl = null)
    {
        $this->pay_orderid = $pay_orderid;

        $pay_bankcode = $this->pay_bankcode($pay_bankcode);

        $this->pay_amount($param);

        $this->pay_callbackurl($pay_callbackurl);  //页面跳转返回地址

        $native = array(
            "pay_memberid" => $this->pay_memberid,
            "pay_orderid" => $this->pay_orderid,
            "pay_amount" => $this->pay_amount,
            "pay_applydate" => $param['add_time'],
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $this->pay_notifyurl,
            "pay_callbackurl" => $this->pay_callbackurl,
        );
        ksort($native);

        $this->sign($native,$this->Md5key);

        $native["pay_md5sign"] = $this->pay_md5sign;
        $native['pay_attach'] = "";
        $native['pay_productname'] = $paymentModel->type_name[$param['type']];

        $data = array(
            'tijiao' => $this->tjurl,
            'native' => (object)$native
        );
        return $data;
    }

    public function sign($native,$Md5key)
    {
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        return $this->pay_md5sign = strtoupper(md5($md5str . "key=" . $Md5key));
    }

    public function pay_amount($data)
    {
        $this->pay_amount = $data['money'];//单位：分
    }

    public function pay_bankcode($pay_bankcode)
    {
        return array_search($pay_bankcode,$this->pay_bankcode);
    }

    public function pay_callbackurl($pay_callbackurl)
    {
        return $this->pay_callbackurl = empty($pay_callbackurl) ? $this->pay_callbackurl : $pay_callbackurl;
    }

    /**
     * 验证是否符合规则
     * @param $pay_bankcode
     * @param $money
     * @return bool
     */
    public function check_money($pay_bankcode,$money)
    {
        $res = false;
        if($pay_bankcode == PayPaymentModel::PAY_MODE_ALIPAY && in_array($money,$this->pay_amount_alipay)){
            $res = true;
        }
        if($pay_bankcode == PayPaymentModel::PAY_MODE_WECHAT_H5 && in_array($money,$this->pay_amount_wechat_h5)){
            $res = true;
        }

        return $res;
    }
}
