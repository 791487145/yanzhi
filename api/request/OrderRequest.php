<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------
namespace api\request;

use api\common\model\CommonModel;

use api\model\PayPaymentModel;
use api\pay\service\PayBeiweiService;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class OrderRequest extends Validate
{
    static $type;
    static $mode;
    static $money;

    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);
        $request = Request::instance();
        $data = $request->only(['type','mode','money']);
        self::$type = $data['type'];
        self::$money = $data['money'];
        self::$mode = $data['mode'];
    }

    protected $rule =   [
        'type'  => 'require|number',
        'mode'   => 'require|integer|elt:2',
        'money' => 'require|integer|egt:1|check_money|check_pay_vip_money',
    ];

    protected $message  =   [
        'type.require' => '请选择充值类型',
        'type.number'     => '充值类型参数类型错误',
        'mode.require'   => '请选择支付方式',
        'mode.integer'  => '支付方式参数错误',
        'mode.elt'  => '支付方式参数值错误',
        'money.require'        => '请填写充值金额',
        'money.integer'        => '充值金额参数类型错误',
        'money.egt'        => '充值金额参数大小错误',
    ];

   protected function check_money()
   {
       $payBeiweiService = new PayBeiweiService();
       if(!$payBeiweiService->check_money(self::$mode,self::$money)){
           return '充值金额不在范围内';
       };
   }

   protected function check_pay_vip_money()
   {
       if(self::$type == PayPaymentModel::TYPE_VIP){
           $paymentModel = new PayPaymentModel();
           $i = $paymentModel->check_pay_vip_money(self::$money);
           if($i == 0 || $i > 1){
              return 'vip充值金额不正确';
           }
       }
   }
}
